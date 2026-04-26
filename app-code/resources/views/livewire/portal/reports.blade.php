<div>
    <h1 class="portal-title text-3xl font-extrabold mb-1">Monthly Reports</h1>
    <p class="portal-muted text-sm mb-6">Your monthly site health report, delivered automatically.</p>

    @if ($reports->isEmpty())
        <div class="portal-panel-strong rounded-3xl p-10 text-center">
            <p class="portal-muted text-sm">
                Your first monthly report will be ready on
                <strong>{{ now()->addMonthNoOverflow()->startOfMonth()->format('F j') }}</strong>.
                We'll email it to you when it's done.
            </p>
        </div>
    @else
        <div class="portal-panel-strong rounded-3xl overflow-hidden">
            @foreach ($reports as $report)
                <div class="flex items-center justify-between gap-4 px-6 py-5 border-b last:border-b-0 portal-divider">
                    <div>
                        <p class="portal-title text-sm font-bold">
                            {{ \Carbon\Carbon::parse($report->period . '-01')->format('F Y') }}
                        </p>
                        <p class="portal-muted text-xs mt-0.5">
                            Generated {{ $report->created_at->format('M j') }}
                        </p>
                    </div>
                    <button wire:click="downloadReport('{{ $report->id }}')"
                            class="portal-btn-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download PDF
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
