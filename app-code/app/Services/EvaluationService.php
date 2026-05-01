<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\SiteEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * EvaluationService — manages the Path B (new client) evaluation lifecycle.
 *
 * Monthly cap: configurable via EVALUATION_MONTHLY_CAP env var (default: 10).
 * When cap is hit, new submissions are auto-waitlisted.
 */
class EvaluationService
{
    private string $tenantId;
    private string $from;
    private string $appUrl;
    private int    $monthlyCap;

    public function __construct()
    {
        $this->tenantId   = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
        $this->from       = config('services.resend.from', 'team@reviveguard.com');
        $this->appUrl     = rtrim(config('app.url'), '/');
        $this->monthlyCap = (int) config('app.evaluation_monthly_cap', 10);
    }

    // ── Submit evaluation ─────────────────────────────────────────────────────

    /**
     * Create a new evaluation from the public form submission.
     * Automatically waitlists if this month's cap has been reached.
     *
     * Returns the created SiteEvaluation.
     */
    public function submit(
        string  $name,
        string  $email,
        string  $siteUrl,
        string  $siteType  = 'wordpress',
        ?string $concern   = null,
        ?string $ipAddress = null,
        ?string $referrer  = null,
    ): SiteEvaluation {
        $monthSlot  = Carbon::now()->format('Y-m');
        $slotCount  = SiteEvaluation::where('tenant_id', $this->tenantId)
            ->where('month_slot', $monthSlot)
            ->whereNotIn('status', ['declined', 'expired'])
            ->count();

        $waitlisted = $slotCount >= $this->monthlyCap;

        $evaluation = SiteEvaluation::create([
            'tenant_id'      => $this->tenantId,
            'prospect_name'  => $name,
            'prospect_email' => strtolower(trim($email)),
            'site_url'       => $siteUrl,
            'site_type'      => $siteType,
            'concern'        => $concern,
            'status'         => 'pending',
            'waitlisted'     => $waitlisted,
            'ip_address'     => $ipAddress,
            'referrer_url'   => $referrer,
        ]);

        // Send confirmation email
        $this->sendConfirmationEmail($evaluation, $waitlisted);

        Log::info('EvaluationService: evaluation submitted', [
            'evaluation_id' => $evaluation->id,
            'email'         => $evaluation->prospect_email,
            'waitlisted'    => $waitlisted,
        ]);

        return $evaluation;
    }

    // ── Admin: send proposal ──────────────────────────────────────────────────

    /**
     * Admin marks evaluation as reviewed and sends a proposal invite.
     * This generates a ClientInvite (path=evaluation) and sets month_slot.
     *
     * Returns the generated plain token so InviteService can send the email.
     */
    public function sendProposal(SiteEvaluation $evaluation, ?string $planId = null, ?string $adminId = null): string
    {
        /** @var InviteService $inviteService */
        $inviteService = app(InviteService::class);

        $monthSlot = Carbon::now()->format('Y-m');

        // Generate and send invite
        [$invite, $plainToken] = $inviteService->create(
            tenantId:     $this->tenantId,
            name:         $evaluation->prospect_name,
            email:        $evaluation->prospect_email,
            path:         'evaluation',
            siteUrl:      $evaluation->site_url,
            evaluationId: $evaluation->id,
            createdBy:    $adminId,
            ttlHours:     72,
        );

        // Generate proposal token hash for the evaluation row itself
        $proposalToken = bin2hex(random_bytes(16));
        $proposalHash  = hash('sha256', $proposalToken);

        $evaluation->update([
            'status'               => 'proposed',
            'recommended_plan_id'  => $planId,
            'reviewed_by'          => $adminId,
            'reviewed_at'          => Carbon::now(),
            'proposal_token_hash'  => $proposalHash,
            'proposal_sent_at'     => Carbon::now(),
            'proposal_expires_at'  => Carbon::now()->addHours(72),
            'month_slot'           => $monthSlot,
        ]);

        // Send the invite email via InviteService
        $inviteService->sendEmail($invite, $plainToken);

        Log::info('EvaluationService: proposal sent', [
            'evaluation_id' => $evaluation->id,
            'invite_id'     => $invite->id,
        ]);

        return $plainToken;
    }

    // ── Admin: decline ────────────────────────────────────────────────────────

    public function decline(SiteEvaluation $evaluation): void
    {
        $evaluation->update([
            'status'      => 'declined',
            'declined_at' => Carbon::now(),
        ]);

        Log::info('EvaluationService: evaluation declined', ['id' => $evaluation->id]);
    }

    // ── Scheduled: expire proposals ──────────────────────────────────────────

    /**
     * Called by scheduler — expire all proposed evaluations where proposal_expires_at has passed.
     * Returns the number of records expired.
     */
    public function expireStaleProposals(): int
    {
        $expired = SiteEvaluation::where('tenant_id', $this->tenantId)
            ->where('status', 'proposed')
            ->where('proposal_expires_at', '<', Carbon::now())
            ->get();

        foreach ($expired as $evaluation) {
            $evaluation->update([
                'status'     => 'expired',
                'expired_at' => Carbon::now(),
            ]);
        }

        if ($expired->count() > 0) {
            Log::info('EvaluationService: expired stale proposals', ['count' => $expired->count()]);
        }

        return $expired->count();
    }

    /**
     * Called by scheduler — send 7-day follow-up to pending evaluations with no action.
     */
    public function sendFollowUps(): int
    {
        $cutoff = Carbon::now()->subDays(7);

        $evaluations = SiteEvaluation::where('tenant_id', $this->tenantId)
            ->where('status', 'pending')
            ->whereNull('followup_sent_at')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $sent = 0;
        foreach ($evaluations as $evaluation) {
            try {
                $html = view('emails.evaluation-followup', [
                    'name'    => $evaluation->prospect_name,
                    'siteUrl' => $evaluation->site_url,
                ])->render();

                Resend::emails()->send([
                    'from'    => $this->from,
                    'to'      => $evaluation->prospect_email,
                    'subject' => 'Still thinking about ReviveGuard?',
                    'html'    => $html,
                ]);

                $evaluation->update(['followup_sent_at' => Carbon::now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('EvaluationService: follow-up email failed', [
                    'id'    => $evaluation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sendConfirmationEmail(SiteEvaluation $evaluation, bool $waitlisted): void
    {
        $view    = 'emails.evaluation-confirmation';
        $subject = $waitlisted
            ? 'We received your evaluation request — you\'re on the waitlist'
            : 'We received your evaluation request';

        try {
            $html = view($view, [
                'name'       => $evaluation->prospect_name,
                'siteUrl'    => $evaluation->site_url,
                'waitlisted' => $waitlisted,
            ])->render();

            Resend::emails()->send([
                'from'    => $this->from,
                'to'      => $evaluation->prospect_email,
                'subject' => $subject,
                'html'    => $html,
            ]);
        } catch (\Throwable $e) {
            Log::error('EvaluationService: confirmation email failed', [
                'id'    => $evaluation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
