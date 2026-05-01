<?php

namespace App\Http\Controllers;

use App\Services\EvaluationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * EvaluationController — handles the public site evaluation form.
 *
 * GET  /evaluate        → show the form
 * POST /evaluate        → process the submission
 *
 * Monthly cap is enforced inside EvaluationService::submit().
 * Over-cap submissions are auto-waitlisted.
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

        return redirect()->route('evaluate.thanks', ['waitlisted' => $evaluation->waitlisted ? '1' : '0']);
    }

    public function thanks(Request $request)
    {
        $waitlisted = $request->query('waitlisted', '0') === '1';
        return view('evaluate-thanks', compact('waitlisted'));
    }
}
