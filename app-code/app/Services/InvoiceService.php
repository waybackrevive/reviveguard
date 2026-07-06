<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate the next RVG-YYYY-NNN invoice number for the current tenant.
     */
    public function generateInvoiceNumber(string $tenantId): string
    {
        $year  = now()->year;
        $count = Invoice::where('tenant_id', $tenantId)
            ->whereYear('issued_at', $year)
            ->count();

        $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "RVG-{$year}-{$seq}";
    }

    /**
     * Create an Invoice from a Stripe invoice.paid payload.
     */
    public function createFromStripeInvoice(object $stripeInvoice): ?Invoice
    {
        if (empty($stripeInvoice->id)) {
            return null;
        }

        if (Invoice::where('stripe_invoice_id', $stripeInvoice->id)->exists()) {
            return null;
        }

        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');

        $client = Client::where('stripe_id', $stripeInvoice->customer)
            ->orWhere('stripe_test_id', $stripeInvoice->customer)
            ->first();

        if (! $client && ! empty($stripeInvoice->customer_email)) {
            $client = Client::where('email', $stripeInvoice->customer_email)->first();
        }

        if (! $client) {
            Log::warning('InvoiceService: no client found for Stripe invoice', [
                'stripe_invoice_id' => $stripeInvoice->id,
                'customer'          => $stripeInvoice->customer ?? null,
            ]);

            throw new \RuntimeException('Client not found for Stripe invoice: ' . $stripeInvoice->id);
        }

        $subscription = null;
        $stripeSubId  = $this->resolveStripeSubscriptionId($stripeInvoice);

        if ($stripeSubId) {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubId)->first();
        }

        $issuedAt = isset($stripeInvoice->created)
            ? \Carbon\Carbon::createFromTimestamp((int) $stripeInvoice->created)
            : now();

        $periodStart = isset($stripeInvoice->period_start)
            ? \Carbon\Carbon::createFromTimestamp((int) $stripeInvoice->period_start)->toDateString()
            : $issuedAt->copy()->startOfMonth()->toDateString();

        $periodEnd = isset($stripeInvoice->period_end)
            ? \Carbon\Carbon::createFromTimestamp((int) $stripeInvoice->period_end)->toDateString()
            : $issuedAt->copy()->endOfMonth()->toDateString();

        $amountCents = (int) ($stripeInvoice->amount_paid ?? $stripeInvoice->total ?? 0);

        $lineItems = [];
        foreach ($stripeInvoice->lines->data ?? [] as $line) {
            $lineItems[] = [
                'description'  => $line->description ?? 'ReviveGuard subscription',
                'amount_cents' => (int) ($line->amount ?? 0),
            ];
        }

        if ($lineItems === []) {
            $lineItems[] = [
                'description'  => 'ReviveGuard subscription',
                'amount_cents' => $amountCents,
            ];
        }

        return Invoice::create([
            'tenant_id'                 => $tenantId,
            'client_id'                 => $client->id,
            'subscription_id'           => $subscription?->id,
            'invoice_number'            => $this->generateInvoiceNumber($tenantId),
            'period_start'              => $periodStart,
            'period_end'                => $periodEnd,
            'issued_at'                 => $issuedAt->toDateString(),
            'subtotal_cents'            => $amountCents,
            'tax_cents'                 => 0,
            'total_cents'               => $amountCents,
            'currency'                  => strtoupper($stripeInvoice->currency ?? 'USD'),
            'status'                    => 'paid',
            'stripe_invoice_id'         => $stripeInvoice->id,
            'stripe_hosted_invoice_url' => $stripeInvoice->hosted_invoice_url ?? null,
            'line_items'                => $lineItems,
        ]);
    }

    /**
     * Local receipt when a plan changes without a separate Stripe charge (e.g. downgrade credit).
     */
    public function createPlanChangeReceipt(
        Client $client,
        Subscription $subscription,
        Plan $from,
        Plan $to,
        bool $isUpgrade,
        ?string $referenceKey = null,
    ): ?Invoice {
        $referenceKey ??= 'plan_change:' . $subscription->id . ':' . now()->timestamp;

        if (Invoice::where('reference_key', $referenceKey)->exists()) {
            return null;
        }

        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
        $today    = now()->toDateString();
        $action   = $isUpgrade ? 'Upgrade' : 'Plan change';

        $description = $isUpgrade
            ? "{$action}: {$from->name} → {$to->name}"
            : "{$action}: {$from->name} → {$to->name} (credit on next bill)";

        return Invoice::create([
            'tenant_id'       => $tenantId,
            'client_id'       => $client->id,
            'subscription_id' => $subscription->id,
            'invoice_number'  => $this->generateInvoiceNumber($tenantId),
            'period_start'    => $today,
            'period_end'      => $today,
            'issued_at'       => $today,
            'subtotal_cents'  => 0,
            'tax_cents'       => 0,
            'total_cents'     => 0,
            'currency'        => 'USD',
            'status'          => 'paid',
            'reference_key'   => $referenceKey,
            'line_items'      => [[
                'description'  => $description,
                'amount_cents' => 0,
            ]],
        ]);
    }

    /**
     * Backfill plan-change receipts from portal activity events (for changes before invoice sync).
     */
    public function backfillPlanChangeReceipts(Client $client): int
    {
        $events = Event::query()
            ->where('type', 'client_action')
            ->where('metadata->client_id', $client->id)
            ->whereIn('metadata->action', ['plan_upgraded', 'plan_downgraded'])
            ->orderBy('created_at')
            ->get();

        $created = 0;

        foreach ($events as $event) {
            $referenceKey = 'plan_change:' . $event->id;

            if (Invoice::where('reference_key', $referenceKey)->exists()) {
                continue;
            }

            $fromSlug = $event->metadata['from'] ?? null;
            $toSlug   = $event->metadata['to'] ?? null;

            if (! $fromSlug || ! $toSlug) {
                continue;
            }

            $from = Plan::where('slug', $fromSlug)->first();
            $to   = Plan::where('slug', $toSlug)->first();

            if (! $from || ! $to) {
                continue;
            }

            $subscription = $event->site_id
                ? Subscription::where('site_id', $event->site_id)->orderByDesc('created_at')->first()
                : $client->subscriptions()->orderByDesc('created_at')->first();

            if (! $subscription) {
                continue;
            }

            $isUpgrade = \App\Support\PlanCatalog::isUpgrade($from, $to);

            if ($this->createPlanChangeReceipt($client, $subscription, $from, $to, $isUpgrade, $referenceKey)) {
                $created++;
            }
        }

        return $created;
    }

    private function resolveStripeSubscriptionId(object $stripeInvoice): ?string
    {
        if (! empty($stripeInvoice->subscription)) {
            return is_string($stripeInvoice->subscription)
                ? $stripeInvoice->subscription
                : ($stripeInvoice->subscription->id ?? null);
        }

        $parent = $stripeInvoice->parent ?? null;

        if ($parent && ($parent->type ?? '') === 'subscription_details') {
            return $parent->subscription_details->subscription ?? null;
        }

        foreach ($stripeInvoice->lines->data ?? [] as $line) {
            if (! empty($line->subscription)) {
                return is_string($line->subscription) ? $line->subscription : ($line->subscription->id ?? null);
            }
        }

        return null;
    }

    /**
     * Import a Stripe invoice if we do not already have it locally.
     */
    public function importStripeInvoice(object $stripeInvoice): ?Invoice
    {
        if (empty($stripeInvoice->id)) {
            return null;
        }

        $existing = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();

        if ($existing) {
            if (empty($existing->stripe_hosted_invoice_url) && ! empty($stripeInvoice->hosted_invoice_url)) {
                $existing->update(['stripe_hosted_invoice_url' => $stripeInvoice->hosted_invoice_url]);
            }

            return $existing;
        }

        try {
            return $this->createFromStripeInvoice($stripeInvoice);
        } catch (\Throwable $e) {
            Log::warning('InvoiceService: importStripeInvoice failed', [
                'stripe_invoice_id' => $stripeInvoice->id,
                'error'             => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @deprecated Legacy Whop integration — retained for historical records only.
     */
    public function createFromWhopCharge(array $chargeData): Invoice
    {
        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');

        $client = Client::where('tenant_id', $tenantId)
            ->where('whop_member_id', $chargeData['membership_id'] ?? null)
            ->first();

        if (! $client) {
            Log::warning('InvoiceService: no client found for whop_member_id', [
                'membership_id' => $chargeData['membership_id'] ?? null,
            ]);
            throw new \RuntimeException('Client not found for membership_id: ' . ($chargeData['membership_id'] ?? 'null'));
        }

        $issuedAt    = isset($chargeData['created_at'])
            ? \Carbon\Carbon::createFromTimestamp($chargeData['created_at'])
            : now();
        $periodStart = $issuedAt->copy()->startOfMonth()->toDateString();
        $periodEnd   = $issuedAt->copy()->endOfMonth()->toDateString();

        $amountCents = (int) ($chargeData['amount_cents'] ?? 0);

        $invoice = Invoice::create([
            'tenant_id'       => $tenantId,
            'client_id'       => $client->id,
            'invoice_number'  => $this->generateInvoiceNumber($tenantId),
            'period_start'    => $periodStart,
            'period_end'      => $periodEnd,
            'issued_at'       => $issuedAt->toDateString(),
            'subtotal_cents'  => $amountCents,
            'tax_cents'       => 0,
            'total_cents'     => $amountCents,
            'currency'        => strtoupper($chargeData['currency'] ?? 'USD'),
            'status'          => 'paid',
            'whop_charge_id'  => $chargeData['id'] ?? null,
            'line_items'      => [
                [
                    'description' => 'ReviveGuard subscription',
                    'amount_cents' => $amountCents,
                ],
            ],
        ]);

        return $invoice;
    }

    /**
     * Generate a PDF binary for the invoice via the Puppeteer microservice.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $html = view('emails.invoice-pdf', ['invoice' => $invoice])->render();

        $puppeteerUrl = config('services.puppeteer.url', 'http://127.0.0.1:3002/render');

        $ch = curl_init($puppeteerUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode(['html' => $html]),
            CURLOPT_TIMEOUT        => 30,
        ]);

        $pdf = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code !== 200 || ! $pdf) {
            Log::error('InvoiceService: Puppeteer PDF generation failed', [
                'invoice_id' => $invoice->id,
                'http_code'  => $code,
                'curl_error' => $err,
            ]);
            throw new \RuntimeException("PDF generation failed for invoice {$invoice->invoice_number}: HTTP {$code}");
        }

        return $pdf;
    }

    /**
     * Upload a PDF binary to Backblaze B2 and persist key + url on the invoice.
     * Returns the B2 object key.
     */
    public function uploadToB2(Invoice $invoice, string $pdfBinary): string
    {
        $key = "invoices/{$invoice->client_id}/{$invoice->invoice_number}.pdf";

        // Uses the 'b2' disk configured in config/filesystems.php
        Storage::disk('b2')->put($key, $pdfBinary, [
            'visibility'  => 'private',
            'ContentType' => 'application/pdf',
        ]);

        $url = Storage::disk('b2')->url($key);

        $invoice->update([
            'pdf_b2_key'       => $key,
            'pdf_url'          => $url,
            'pdf_generated_at' => now(),
        ]);

        return $key;
    }

    /**
     * Generate a short-lived signed download URL from B2 (default 1 hour).
     */
    public function getSignedUrl(Invoice $invoice, int $ttlMinutes = 60): string
    {
        if (! $invoice->pdf_b2_key) {
            throw new \RuntimeException("Invoice {$invoice->invoice_number} has no PDF stored.");
        }

        return Storage::disk('b2')->temporaryUrl(
            $invoice->pdf_b2_key,
            now()->addMinutes($ttlMinutes)
        );
    }

    /**
     * Full pipeline: generate PDF, upload to B2, return signed URL.
     */
    public function buildAndStore(Invoice $invoice): string
    {
        $pdf = $this->generatePdf($invoice);
        $this->uploadToB2($invoice, $pdf);
        return $this->getSignedUrl($invoice);
    }
}
