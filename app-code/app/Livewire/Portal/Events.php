<?php

namespace App\Livewire\Portal;

use App\Models\Event;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Events extends Component
{
    use WithPagination;

    public string $filterType     = '';
    public string $filterSeverity = '';
    public int    $filterDays     = 30;

    // Selected event for modal
    public ?Event $selectedEvent = null;

    protected $queryString = [
        'filterType'     => ['as' => 'type'],
        'filterSeverity' => ['as' => 'severity'],
        'filterDays'     => ['as' => 'days', 'except' => 30],
    ];

    public function updatingFilterType(): void     { $this->resetPage(); }
    public function updatingFilterSeverity(): void { $this->resetPage(); }
    public function updatingFilterDays(): void     { $this->resetPage(); }

    public function showEvent(string $id): void
    {
        $this->selectedEvent = Event::find($id);
    }

    public function closeModal(): void
    {
        $this->selectedEvent = null;
    }

    public function render(): \Illuminate\View\View
    {
        $client  = Auth::guard('client')->user();
        $siteIds = Site::where('client_id', $client->id)->pluck('id');

        $query = Event::whereIn('site_id', $siteIds)
            ->where('created_at', '>=', now()->subDays($this->filterDays))
            ->orderByDesc('created_at');

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterSeverity !== '') {
            $query->where('severity', $this->filterSeverity);
        }

        return view('livewire.portal.events', [
            'events' => $query->paginate(20),
        ])->layout('portal.layouts.app');
    }
}
