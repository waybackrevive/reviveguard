<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">Monthly Reports</h1>
    <p class="text-sm text-gray-500 mb-6">Your monthly site health report, delivered automatically.</p>

    @if ($reports->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <p class="text-gray-500 text-sm">
                Your first monthly report will be ready on
                <strong>{{ now()->addMonthNoOverflow()->startOfMonth()->format('F j') }}</strong>.
                We'll email it to you when it's done.
            </p>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($reports as $report)
                <div class="flex items-center justify-between px-6 py-5">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ \Carbon\Carbon::parse($report->period . '-01')->format('F Y') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Generated {{ $report->created_at->format('M j') }}
                        </p>
                    </div>
                    <button wire:click="downloadReport('{{ $report->id }}')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">
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
