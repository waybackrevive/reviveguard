<?php

namespace App\Http\Controllers;

use App\Models\SiteEvaluation;
use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * EvaluationController — handles the public site evaluation form.
 *
 * GET  /evaluate               → show the form
 * POST /evaluate               → process the submission
 * GET  /evaluate/thanks        → confirmation page
 * GET  /evaluate/{id}/report   → show plugin report upload form
 * POST /evaluate/{id}/report   → accept plugin report token
 */
class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluationService) {}

    public function show()
    {
        return view('evaluate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'site_url'  => ['required', 'url', 'max:500'],
            'site_type' => ['required', Rule::in(['wordpress', 'html', 'other'])],
            'concern'   => ['nullable', 'string', 'max:2000'],
        ]);

        $evaluation = $this->evaluationService->submit(
            name:      $validated['name'],
            email:     $validated['email'],
            siteUrl:   $validated['site_url'],
            siteType:  $validated['site_type'],
            concern:   $validated['concern'] ?? null,
            ipAddress: $request->ip(),
            referrer:  $request->header('referer'),
        );

        return redirect()->route('evaluate.thanks', [
            'id'         => $evaluation->id,
            'waitlisted' => $evaluation->waitlisted ? '1' : '0',
        ]);
    }

    public function thanks(Request $request)
    {
        $waitlisted   = $request->query('waitlisted', '0') === '1';
        $evaluationId = $request->query('id');
        return view('evaluate-thanks', compact('waitlisted', 'evaluationId'));
    }

    /** Show the plugin report upload form */
    public function showReport(string $id)
    {
        $evaluation = SiteEvaluation::findOrFail($id);

        // If report already received, show confirmation view
        if ($evaluation->plugin_report !== null) {
            return view('evaluate-report-done', ['evaluation' => $evaluation]);
        }

        // Pass the shared secret so the prospect can configure the WP plugin
        $pluginSecret = config('app.health_check_secret', '');

        return view('evaluate-report', compact('evaluation', 'pluginSecret'));
    }

    /** Accept and store the plugin report token */
    public function storeReport(Request $request, string $id)
    {
        $evaluation = SiteEvaluation::findOrFail($id);

        if ($evaluation->plugin_report !== null) {
            return back()->with('info', 'We already have your health report — thank you!');
        }

        $validated = $request->validate([
            'report_token' => ['required', 'string', 'max:8000'],
        ]);

        try {
            $this->evaluationService->submitPluginReport($evaluation, trim($validated['report_token']));
            return redirect()->route('evaluate.report.show', $id)
                ->with('success', 'Report received! Your evaluation is now prioritised.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['report_token' => $e->getMessage()])->withInput();
        }
    }
}

