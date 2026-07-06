<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #fff;
            padding: 48px 56px;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 22px; font-weight: 700; color: #1e40af; letter-spacing: -0.5px; }
        .logo span { color: #60a5fa; }
        .invoice-meta { text-align: right; font-size: 12px; color: #6b7280; }
        .invoice-meta h1 { font-size: 26px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .invoice-meta p { margin-top: 2px; }

        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 36px; }
        .party-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; margin-bottom: 6px; }
        .party-name { font-weight: 600; color: #111827; margin-bottom: 2px; }
        .party-detail { color: #6b7280; line-height: 1.5; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { background: #f3f4f6; }
        th { padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:last-child td { border-bottom: none; }

        .totals { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .totals-table { width: 260px; }
        .totals-table td { padding: 4px 12px; font-size: 13px; }
        .totals-table .total-row td { font-weight: 700; font-size: 15px; border-top: 2px solid #1e40af; padding-top: 8px; color: #1e40af; }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .status-paid   { background: #d1fae5; color: #065f46; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .status-void   { background: #f3f4f6; color: #4b5563; }

        .footer { border-top: 1px solid #e5e7eb; padding-top: 20px; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div>
            <div class="logo">Revive<span>Guard</span></div>
            <div style="font-size:11px;color:#6b7280;margin-top:4px;">app.reviveguard.com</div>
        </div>
        <div class="invoice-meta">
            <h1>INVOICE</h1>
            <p><strong>{{ $invoice->invoice_number }}</strong></p>
            <p>Issued: {{ $invoice->issued_at instanceof \Carbon\Carbon ? $invoice->issued_at->format('M j, Y') : \Carbon\Carbon::parse($invoice->issued_at)->format('M j, Y') }}</p>
            <p style="margin-top:6px;">
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </p>
        </div>
    </div>

    {{-- Parties --}}
    <div class="parties">
        <div>
            <div class="party-label">From</div>
            <div class="party-name">ReviveGuard</div>
            <div class="party-detail">team@reviveguard.com</div>
        </div>
        <div>
            <div class="party-label">Bill To</div>
            <div class="party-name">{{ $invoice->client->name ?? 'Client' }}</div>
            <div class="party-detail">{{ $invoice->client->email ?? '' }}</div>
        </div>
    </div>

    {{-- Line items --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right;width:140px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->line_items ?? [] as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'Service' }}</td>
                    <td style="text-align:right;">${{ number_format(($item['amount_cents'] ?? 0) / 100, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>ReviveGuard subscription</td>
                    <td style="text-align:right;">{{ $invoice->formatted_total }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td style="color:#6b7280;">Subtotal</td>
                <td style="text-align:right;">{{ $invoice->subtotal }}</td>
            </tr>
            @if ($invoice->tax_cents > 0)
            <tr>
                <td style="color:#6b7280;">Tax</td>
                <td style="text-align:right;">{{ $invoice->tax }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total</td>
                <td style="text-align:right;">{{ $invoice->formatted_total }}</td>
            </tr>
        </table>
    </div>

    {{-- Period --}}
    <p style="font-size:11px;color:#6b7280;margin-bottom:40px;">
        Service period:
        {{ \Carbon\Carbon::parse($invoice->period_start)->format('M j, Y') }}
        &ndash;
        {{ \Carbon\Carbon::parse($invoice->period_end)->format('M j, Y') }}
        @if ($invoice->stripe_invoice_id)
            &nbsp;&middot;&nbsp; Stripe: {{ $invoice->stripe_invoice_id }}
        @elseif ($invoice->whop_charge_id)
            &nbsp;&middot;&nbsp; Charge ID: {{ $invoice->whop_charge_id }}
        @endif
    </p>

    {{-- Footer --}}
    <div class="footer">
        ReviveGuard &mdash; WordPress Care & Recovery &mdash; team@reviveguard.com
    </div>
</body>
</html>
