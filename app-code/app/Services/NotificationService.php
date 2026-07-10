<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Client;
use App\Models\PlatformSetting;
use App\Models\Report;
use App\Models\Site;
use App\Models\Ticket;
use App\Support\PortalUrl;
use Resend\Laravel\Facades\Resend;
use Illuminate\Support\Facades\Log;

/**
 * Central notification service — sends all client-facing emails via Resend.
 *
 * Each method:
 *  1. Builds the email payload
 *  2. Sends via Resend
 *  3. Logs to notification_logs table (see NotificationLog model)
 *  4. Catches all exceptions to prevent alert failures from breaking the caller
 */
class NotificationService
{
    private string $from;

    public function __construct()
    {
        $this->from = PlatformSetting::get('resend_from', config('services.resend.from', 'notifications@reviveguard.com')) ?? 'notifications@reviveguard.com';
    }

    // ── Site status alerts ────────────────────────────────────────────────────

    public function sendSiteDown(Site $site): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "⚠ Your website is not responding — {$siteUrl}",
            view: 'emails.site-down',
            data: [
                'clientName'    => explode(' ', $client->name)[0],
                'siteUrl'       => $siteUrl,
                'detectedAt'    => now()->format('g:i A') . ' UTC on ' . now()->format('F j, Y'),
                'dashboardUrl'  => PortalUrl::to('portal/dashboard'),
            ],
            type: 'site_down',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    public function sendSiteRecovered(Site $site, ?string $downtimeDuration = null): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "✓ Your website is back online — {$siteUrl}",
            view: 'emails.site-recovered',
            data: [
                'clientName'       => explode(' ', $client->name)[0],
                'siteUrl'          => $siteUrl,
                'downtimeDuration' => $downtimeDuration ?? 'a short period',
                'recoveredAt'      => now()->format('g:i A') . ' UTC',
                'dashboardUrl'     => PortalUrl::to('portal/dashboard'),
            ],
            type: 'site_recovered',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    // ── SSL / Domain warnings ─────────────────────────────────────────────────

    public function sendSslExpiryWarning(Site $site, int $daysLeft): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl   = $site->url ?? $site->name;
        $expiresOn = $site->ssl_expires_at?->format('F j, Y') ?? 'unknown date';

        $this->send(
            to: $client->email,
            subject: "⚠ Your SSL certificate expires in {$daysLeft} days — {$siteUrl}",
            view: 'emails.ssl-expiry-warning',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'daysLeft'     => $daysLeft,
                'expiresOn'    => $expiresOn,
                'dashboardUrl' => PortalUrl::to('portal/dashboard'),
            ],
            type: 'ssl_expiry_warning',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    public function sendDomainExpiryWarning(Site $site, int $daysLeft): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $domain    = $site->domain ?? $site->url ?? $site->name;
        $expiresOn = $site->domain_expires_at?->format('F j, Y') ?? 'unknown date';

        $this->send(
            to: $client->email,
            subject: "⚠ Your domain expires in {$daysLeft} days — {$domain}",
            view: 'emails.domain-expiry-warning',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'domain'       => $domain,
                'daysLeft'     => $daysLeft,
                'expiresOn'    => $expiresOn,
                'dashboardUrl' => PortalUrl::to('portal/dashboard'),
            ],
            type: 'domain_expiry_warning',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    // ── Backup ────────────────────────────────────────────────────────────────

    public function sendBackupFailed(Backup $backup): void
    {
        $site   = $backup->site;
        $client = $site?->client;
        if (! $client || ! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "⚠ Backup issue on your website — {$siteUrl}",
            view: 'emails.backup-failed',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'failedAt'     => $backup->started_at?->format('F j, Y \a\t g:i A') . ' UTC',
                'errorMessage' => 'Our team has been notified and will investigate.',
                'dashboardUrl' => PortalUrl::to('portal/dashboard'),
            ],
            type: 'backup_failed',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    public function sendReportReady(Report $report, string $pdfPath): void
    {
        $client = $report->client;
        if (! $client || ! $this->shouldNotify($client)) {
            return;
        }

        $site      = $report->site;
        $siteUrl   = $site?->url ?? $site?->name ?? 'your website';
        $period    = \Carbon\Carbon::parse($report->period . '-01')->format('F Y');

        try {
            $pdfContent = file_get_contents($pdfPath);
            $pdfName    = strtolower(str_replace(' ', '-', $period)) . '-site-report.pdf';

            $subject = "Your {$period} site report is ready — {$siteUrl}";

            $response = Resend::emails()->send([
                'from'        => $this->from,
                'to'          => $client->email,
                'subject'     => $subject,
                'html'        => view('emails.report-ready', [
                    'clientName'   => explode(' ', $client->name)[0],
                    'siteUrl'      => $siteUrl,
                    'period'       => $period,
                    'uptime30d'    => $site?->uptime_30d,
                    'dashboardUrl' => PortalUrl::to('portal/reports'),
                ])->render(),
                'attachments' => [[
                    'filename' => $pdfName,
                    'content'  => base64_encode($pdfContent),
                ]],
            ]);

            $this->logNotification('report_ready', $client->id, $site?->id, $client->email, $subject, true, null, $response->id ?? null);
        } catch (\Throwable $e) {
            Log::error('NotificationService: sendReportReady failed', ['error' => $e->getMessage()]);
            $this->logNotification('report_ready', $client->id, $site?->id, $client->email ?? '', "Your site report is ready", false, $e->getMessage());
        }
    }

    // ── Updates ───────────────────────────────────────────────────────────────

    /**
     * @param  array{wp_core: bool, plugins: string[], errors: string[]}  $summary
     */
    public function sendUpdateComplete(Site $site, array $summary): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl      = $site->url ?? $site->name;
        $pluginCount  = count($summary['plugins'] ?? []);
        $coreUpdated  = $summary['wp_core'] ?? false;

        $subject = $coreUpdated && $pluginCount > 0
            ? "WordPress core and {$pluginCount} plugin(s) updated — {$siteUrl}"
            : ($coreUpdated
                ? "WordPress core updated — {$siteUrl}"
                : "{$pluginCount} plugin(s) updated — {$siteUrl}");

        $this->send(
            to: $client->email,
            subject: $subject,
            view: 'emails.update-complete',
            data: [
                'clientName'  => explode(' ', $client->name)[0],
                'siteUrl'     => $siteUrl,
                'coreUpdated' => $coreUpdated,
                'plugins'     => $summary['plugins'] ?? [],
                'errors'      => $summary['errors'] ?? [],
                'dashboardUrl'=> PortalUrl::to('portal/events'),
            ],
            type: 'update_complete',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    public function sendUpdateFailed(Site $site, string $errorMessage): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "⚠ WordPress update issue — we're restoring your site — {$siteUrl}",
            view: 'emails.update-failed',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'errorMessage' => $errorMessage,
                'dashboardUrl' => PortalUrl::to('portal/events'),
            ],
            type: 'update_failed',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    public function sendRollbackComplete(Site $site): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "✓ Your site was restored from backup — {$siteUrl}",
            view: 'emails.rollback-complete',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'dashboardUrl' => PortalUrl::to('portal/dashboard'),
            ],
            type: 'rollback_complete',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    public function sendRollbackFailed(Site $site, string $errorMessage): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "⚠ Urgent: rollback needs attention — {$siteUrl}",
            view: 'emails.rollback-failed',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'errorMessage' => $errorMessage,
                'dashboardUrl' => PortalUrl::to('portal/tickets'),
            ],
            type: 'rollback_failed',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $findings
     */
    public function sendMalwareScanAlert(Site $site, array $findings, int $criticalCount, int $warningCount): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: $criticalCount > 0
                ? "⚠ Security alert: malware scan findings — {$siteUrl}"
                : "Security notice: malware scan warnings — {$siteUrl}",
            view: 'emails.malware-scan-alert',
            data: [
                'clientName'    => explode(' ', $client->name)[0],
                'siteUrl'       => $siteUrl,
                'criticalCount' => $criticalCount,
                'warningCount'  => $warningCount,
                'findings'      => array_slice($findings, 0, 5),
                'dashboardUrl'  => PortalUrl::to('portal/sites/'.$site->id.'?tab=security'),
                'ticketsUrl'    => PortalUrl::to('portal/tickets'),
            ],
            type: 'malware_scan_alert',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     */
    public function sendBrokenLinkAuditAlert(Site $site, int $brokenCount, array $samples): void
    {
        $client = $site->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $siteUrl = $site->url ?? $site->name;

        $this->send(
            to: $client->email,
            subject: "Broken links found on your site — {$siteUrl}",
            view: 'emails.broken-link-audit',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'siteUrl'      => $siteUrl,
                'brokenCount'  => $brokenCount,
                'samples'      => array_slice($samples, 0, 5),
                'dashboardUrl' => PortalUrl::to('portal/sites/'.$site->id.'?tab=security'),
                'ticketsUrl'   => PortalUrl::to('portal/tickets'),
            ],
            type: 'broken_link_audit',
            siteId: $site->id,
            clientId: $client->id,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $findings
     */
    public function sendAdminSecurityAlert(string $adminEmail, Site $site, string $kind, array $findings): void
    {
        $siteUrl = $site->url ?? $site->name;
        $label   = $kind === 'malware' ? 'Malware scan' : 'Broken link audit';

        $this->send(
            to: $adminEmail,
            subject: "[Ops] {$label} alert — {$siteUrl}",
            view: 'emails.admin-security-alert',
            data: [
                'siteUrl'   => $siteUrl,
                'siteName'  => $site->name,
                'kind'      => $label,
                'findings'  => array_slice($findings, 0, 8),
                'adminUrl'  => url('/admin/sites/'.$site->id.'/edit'),
            ],
            type: 'admin_security_alert',
            clientId: $site->client_id,
            siteId: $site->id,
        );
    }

    // ── Onboarding ────────────────────────────────────────────────────────────

    /**
     * New client welcome email. Includes a magic activation link so the client
     * can set their own password and land straight on the dashboard.
     *
     * @param  string  $activationUrl  Signed, one-time activation URL (72 h TTL)
     */
    public function sendWelcome(Client $client, ?\App\Models\Plan $plan = null, string $activationUrl = ''): void
    {
        $this->send(
            to: $client->email,
            subject: 'Welcome to ReviveGuard — activate your account',
            view: 'emails.welcome',
            data: [
                'clientName'    => explode(' ', $client->name)[0],
                'planName'      => $plan?->name ?? 'ReviveGuard',
                'activationUrl' => $activationUrl,
            ],
            type: 'welcome',
            clientId: $client->id,
        );
    }

    /**
     * Sent to an existing client when their plan is changed, upgraded, or reactivated.
     */
    public function sendPlanUpdated(Client $client, ?\App\Models\Plan $plan = null, ?\App\Models\Subscription $subscription = null): void
    {
        $this->send(
            to: $client->email,
            subject: 'Your ReviveGuard plan has been updated',
            view: 'emails.plan-updated',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'planName'     => $plan?->name ?? 'ReviveGuard',
                'validUntil'   => $subscription?->nextBillingDate()?->format('F j, Y') ?? null,
                'dashboardUrl' => PortalUrl::to('portal/dashboard'),
            ],
            type: 'plan_updated',
            clientId: $client->id,
        );
    }

    // ── Support tickets ───────────────────────────────────────────────────────

    public function sendTicketReplied(Ticket $ticket): void
    {
        $client = $ticket->client;
        if (! $this->shouldNotify($client)) {
            return;
        }

        $this->send(
            to: $client->email,
            subject: "We've responded to your support request",
            view: 'emails.ticket-replied',
            data: [
                'clientName'   => explode(' ', $client->name)[0],
                'ticketSubject'=> $ticket->subject,
                'adminReply'   => $ticket->admin_reply,
                'dashboardUrl' => PortalUrl::to('portal/tickets'),
            ],
            type: 'ticket_replied',
            siteId: null,
            clientId: $client->id,
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function shouldNotify(?Client $client): bool
    {
        return $client !== null && $client->is_active && ! empty($client->email);
    }

    private function send(
        string  $to,
        string  $subject,
        string  $view,
        array   $data,
        string  $type,
        ?string $clientId,
        ?string $siteId = null,
    ): void {
        try {
            $response = Resend::emails()->send([
                'from'    => $this->from,
                'to'      => $to,
                'subject' => $subject,
                'html'    => view($view, $data)->render(),
            ]);

            $messageId = $response->id ?? null;
            $this->logNotification($type, $clientId, $siteId, $to, $subject, true, null, $messageId);
        } catch (\Throwable $e) {
            Log::error("NotificationService: {$type} email failed", [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);
            $this->logNotification($type, $clientId, $siteId, $to, $subject, false, $e->getMessage());
        }
    }

    private function logNotification(
        string  $type,
        ?string $clientId,
        ?string $siteId,
        string  $recipient,
        string  $subject,
        bool    $success,
        ?string $errorMessage = null,
        ?string $resendMessageId = null,
    ): void {
        try {
            \App\Models\NotificationLog::create([
                'tenant_id'         => config('app.tenant_id', '00000000-0000-0000-0000-000000000001'),
                'client_id'         => $clientId,
                'site_id'           => $siteId,
                'type'              => $type,
                'channel'           => 'email',
                'recipient'         => $recipient,
                'subject'           => $subject,
                'status'            => $success ? 'sent' : 'failed',
                'error_message'     => $errorMessage,
                'resend_message_id' => $resendMessageId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('NotificationService: logNotification failed', ['error' => $e->getMessage()]);
        }
    }
}
