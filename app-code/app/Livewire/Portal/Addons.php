<?php

namespace App\Livewire\Portal;

use App\Models\AddonOrder;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Addons extends Component
{
    public bool $showOrderModal = false;

    public ?string $selectedAddonSlug = null;

    public ?string $orderSiteId = null;

    public int $orderQuantity = 1;

    public string $orderNotes = '';

    public function openOrder(string $slug): void
    {
        $addon = $this->findAddon($slug);

        if (! $addon) {
            session()->flash('error', 'Add-on not found.');

            return;
        }

        $this->selectedAddonSlug = $slug;
        $this->orderQuantity     = 1;
        $this->orderNotes        = '';
        $this->orderSiteId       = $this->defaultSiteId();
        $this->showOrderModal    = true;
    }

    public function closeOrder(): void
    {
        $this->showOrderModal = false;
        $this->selectedAddonSlug = null;
    }

    public function placeOrder(): void
    {
        $addon = $this->selectedAddonSlug ? $this->findAddon($this->selectedAddonSlug) : null;

        if (! $addon) {
            session()->flash('error', 'Add-on not found.');

            return;
        }

        $client = Auth::guard('client')->user();

        $rules = [
            'orderNotes' => ['required', 'string', 'min:10', 'max:2000'],
            'orderQuantity' => ['integer', 'min:1', 'max:99'],
        ];

        if ($addon['requires_site'] ?? false) {
            $rules['orderSiteId'] = ['required', 'exists:sites,id'];
        }

        $this->validate($rules);

        if (! Schema::hasTable('addon_orders')) {
            session()->flash('error', 'Orders are not available yet. Please contact support.');

            return;
        }

        if ($addon['requires_site'] ?? false) {
            $ownsSite = Site::where('id', $this->orderSiteId)
                ->where('client_id', $client->id)
                ->exists();

            if (! $ownsSite) {
                session()->flash('error', 'Please select a valid site.');

                return;
            }
        }

        AddonOrder::create([
            'tenant_id'    => $client->tenant_id,
            'client_id'    => $client->id,
            'site_id'      => $this->orderSiteId ?: null,
            'addon_slug'   => $addon['slug'],
            'addon_name'   => $addon['name'],
            'price_label'  => $addon['price_label'],
            'quantity'     => $this->orderQuantity,
            'client_notes' => $this->orderNotes,
            'status'       => 'pending',
        ]);

        $this->closeOrder();
        session()->flash('success', 'Order placed. Our team will review it and update you here.');
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();
        $sites  = Site::where('client_id', $client->id)->orderBy('name')->get();

        $orders = collect();

        if (Schema::hasTable('addon_orders')) {
            $orders = AddonOrder::with('site')
                ->where('client_id', $client->id)
                ->latest()
                ->limit(20)
                ->get();
        }

        $selectedAddon = $this->selectedAddonSlug ? $this->findAddon($this->selectedAddonSlug) : null;

        return view('livewire.portal.addons', [
            'addons'        => config('reviveguard_addons', []),
            'sites'         => $sites,
            'orders'        => $orders,
            'selectedAddon' => $selectedAddon,
        ])->layout('portal.layouts.app');
    }

    private function findAddon(string $slug): ?array
    {
        $addon = collect(config('reviveguard_addons', []))->firstWhere('slug', $slug);

        return is_array($addon) ? $addon : null;
    }

    private function defaultSiteId(): ?string
    {
        $client = Auth::guard('client')->user();

        return Site::where('client_id', $client->id)->value('id');
    }
}
