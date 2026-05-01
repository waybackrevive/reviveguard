<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
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
     * Create an Invoice from a Whop charge.succeeded payload.
     *
     * Expected $chargeData keys:
     *   id, membership_id (whop_member_id), amount_cents, currency,
     *   created_at (unix ts), plan_id (optional)
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
