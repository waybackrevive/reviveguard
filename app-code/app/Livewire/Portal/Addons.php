<?php

namespace App\Livewire\Portal;

use App\Models\AddonOrder;
use App\Models\Site;
use App\Services\ClientActivityService;
use App\Services\StripeBillingService;
use App\Support\StripeConfig;
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

    public function placeOrder(StripeBillingService $billing, ClientActivityService $activity)
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

        $site = null;

        if ($addon['requires_site'] ?? false) {
            $site = Site::where('id', $this->orderSiteId)
                ->where('client_id', $client->id)
                ->first();

            if (! $site) {
                session()->flash('error', 'Please select a valid site.');

                return;
            }
        }

        if (empty(StripeConfig::secretKey())) {
            session()->flash('error', 'Payment system is not configured yet. Please contact support.');

            return;
        }

        $amountCents = $this->calculateAmountCents($addon, $this->orderQuantity);

        if ($amountCents < 50) {
            session()->flash('error', 'Invalid order amount.');

            return;
        }

        $order = AddonOrder::create([
            'tenant_id'    => $client->tenant_id,
            'client_id'    => $client->id,
            'site_id'      => $site?->id,
            'addon_slug'   => $addon['slug'],
            'addon_name'   => $addon['name'],
            'price_label'  => $addon['price_label'],
            'amount_cents' => $amountCents,
            'quantity'     => $this->orderQuantity,
            'client_notes' => $this->orderNotes,
            'status'       => 'awaiting_payment',
        ]);

        $activity->log(
            $client,
            'addon_order_placed',
            "Add-on requested: {$addon['name']}",
            'Order submitted — proceeding to secure payment.',
            $site,
            ['addon_order_id' => $order->id, 'addon_slug' => $addon['slug'], 'amount_cents' => $amountCents],
        );

        $this->closeOrder();

        try {
            $url = $billing->createAddonCheckoutSession($client, $order);
        } catch (\Throwable $e) {
            session()->flash('error', 'Unable to start payment: ' . $e->getMessage());
            report($e);

            return;
        }

        return redirect()->away($url);
    }

    public function payOrder(string $orderId, StripeBillingService $billing)
    {
        $client = Auth::guard('client')->user();

        $order = AddonOrder::where('id', $orderId)
            ->where('client_id', $client->id)
            ->first();

        if (! $order || ! $order->isAwaitingPayment()) {
            session()->flash('error', 'This order cannot be paid right now.');

            return;
        }

        try {
            $url = $billing->createAddonCheckoutSession($client, $order);
        } catch (\Throwable $e) {
            session()->flash('error', 'Unable to start payment: ' . $e->getMessage());
            report($e);

            return;
        }

        return redirect()->away($url);
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

    private function calculateAmountCents(array $addon, int $quantity): int
    {
        $price = (float) ($addon['price'] ?? 0);

        return (int) round($price * max(1, $quantity) * 100);
    }
}
