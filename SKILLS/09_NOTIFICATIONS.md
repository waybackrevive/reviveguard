# SKILL: Notifications — Email Only (Resend)

> Load this skill before building any alert or notification logic.
> References: `05_MVP_FEATURE_SPEC.md` Feature 9, `04_API_DESIGN.md`

---

## What This Covers
All notification types, `NotificationService`, `SendAlert` job, Resend email integration, and the `notifications_log` table.

**Channel: Email only.** No WhatsApp, no SMS, no Slack. KISS.

---

## Why Email Only

- Resend free tier: **3,000 emails/month** — enough for the entire MVP client base
- WhatsApp Cloud API requires Meta business verification + per-message cost
- Email covers 100% of alert types with zero per-message cost
- Simpler code, easier to debug, no third-party mobile API dependencies

---

## All Notification Types

| Alert Type | Recipient | Queue |
|---|---|---|
| `site_down` | Client + Admin email | `critical` |
| `site_recovered` | Client | `critical` |
| `ssl_expiry_warning` | Client | `default` |
| `domain_expiry_warning` | Client | `default` |
| `backup_failed` | Admin | `default` |
| `monthly_report_ready` | Client (PDF attached) | `default` |
| `update_complete` | Client | `default` |
| `ticket_response` | Client | `default` |
| `payment_failed` | Client | `default` |
| `welcome` | Client | `default` |

`site_down` and `site_recovered` go on the `critical` queue so they process ahead of everything else.

---

## Resend Setup

```bash
# No extra package — Laravel ships with Resend mail transport since Laravel 10
```

### `.env`:
```
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxxx
MAIL_FROM_ADDRESS=notifications@reviveguard.com
MAIL_FROM_NAME="ReviveGuard"
MAIL_ADMIN_ADDRESS=admin@reviveguard.com
```

### `config/mail.php` — add mailer entry:
```php
'resend' => [
    'transport' => 'resend',
],
```

### `config/services.php`:
```php
'resend' => [
    'key' => env('RESEND_API_KEY'),
],
```

---

## `SendAlert` Job

```php
final class SendAlert implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly Site|Client $recipient,
        private readonly string $alertType,
        private readonly array $metadata = [],
    ) {}

    public function uniqueId(): string
    {
        // Dedup: same recipient + type within 5-minute window won't double-send
        $id = $this->recipient->id;
        return "{$id}:{$this->alertType}:" . floor(now()->timestamp / 300);
    }

    public function handle(NotificationService $service): void
    {
        $service->send($this->recipient, $this->alertType, $this->metadata);
    }
}
```

**Dispatching with correct queue:**
```php
// Critical alerts — processed first
SendAlert::dispatch($site, 'site_down')->onQueue('critical');
SendAlert::dispatch($site, 'site_recovered')->onQueue('critical');

// Everything else
SendAlert::dispatch($site, 'ssl_expiry_warning', ['days_left' => 30])->onQueue('default');
```

---

## `NotificationService`

```php
final class NotificationService
{
    public function send(Site|Client $recipient, string $alertType, array $metadata = []): void
    {
        $client = $recipient instanceof Site ? $recipient->client : $recipient;
        $site   = $recipient instanceof Site ? $recipient : null;

        $notification = $this->buildNotification($alertType, $client, $site, $metadata);

        if (!$notification) return;

        $this->sendEmail($client, $notification, $alertType);

        // Admin also gets notified for critical site events
        if (in_array($alertType, ['site_down', 'backup_failed'])) {
            $this->alertAdmin($alertType, $site, $metadata);
        }
    }

    private function buildNotification(
        string $alertType,
        Client $client,
        ?Site $site,
        array $metadata
    ): ?array {
        $domain = $site?->domain ?? 'your site';

        return match ($alertType) {
            'site_down' => [
                'subject' => "⚠️ {$domain} appears to be down",
                'view'    => 'emails.alerts.site-down',
            ],
            'site_recovered' => [
                'subject' => "✅ {$domain} is back online",
                'view'    => 'emails.alerts.site-recovered',
            ],
            'ssl_expiry_warning' => [
                'subject' => "🔒 SSL certificate for {$domain} expires in {$metadata['days_left']} days",
                'view'    => 'emails.alerts.ssl-expiry',
            ],
            'domain_expiry_warning' => [
                'subject' => "📅 Domain {$domain} expires in {$metadata['days_left']} days",
                'view'    => 'emails.alerts.domain-expiry',
            ],
            'monthly_report_ready' => [
                'subject' => "📊 Your monthly website report is ready",
                'view'    => 'emails.reports.monthly-ready',
            ],
            'update_complete' => [
                'subject' => "🔄 Website updates applied to {$domain}",
                'view'    => 'emails.alerts.update-complete',
            ],
            'backup_failed' => [
                // Admin-only — handled in alertAdmin(), skip client email
                'subject' => null,
                'view'    => null,
            ],
            'payment_failed' => [
                'subject' => "💳 Payment failed for your ReviveGuard subscription",
                'view'    => 'emails.billing.payment-failed',
            ],
            'ticket_response' => [
                'subject' => "💬 Update on your support ticket: " . ($metadata['subject'] ?? ''),
                'view'    => 'emails.tickets.response',
            ],
            default => null,
        };
    }

    private function sendEmail(Client $client, array $notification, string $alertType): void
    {
        // backup_failed has no client email — skip
        if (!$notification['view']) return;

        try {
            Mail::to($client->email)
                ->send(new AlertMail(
                    view:     $notification['view'],
                    subject:  $notification['subject'],
                    client:   $client,
                ));

            $this->log($client, $alertType, 'sent');
        } catch (Throwable $e) {
            Log::error("Email send failed for {$client->email}: " . $e->getMessage());
            $this->log($client, $alertType, 'failed', $e->getMessage());
        }
    }

    private function alertAdmin(string $alertType, ?Site $site, array $metadata): void
    {
        $adminEmail = config('mail.admin_address');
        if (!$adminEmail) return;

        try {
            Mail::to($adminEmail)->send(new AdminAlertMail($alertType, $site, $metadata));
        } catch (Throwable $e) {
            Log::error("Admin alert email failed: " . $e->getMessage());
        }
    }

    public function sendWelcomeEmail(Client $client, Plan $plan): void
    {
        try {
            Mail::to($client->email)->send(new WelcomeMail($client, $plan));
            $this->log($client, 'welcome', 'sent');
        } catch (Throwable $e) {
            Log::error("Welcome email failed for {$client->email}: " . $e->getMessage());
            $this->log($client, 'welcome', 'failed', $e->getMessage());
        }
    }

    private function log(Client $client, string $type, string $status, ?string $error = null): void
    {
        DB::table('notifications_log')->insert([
            'tenant_id'  => $client->tenant_id,
            'client_id'  => $client->id,
            'type'       => $type,
            'channel'    => 'email',
            'status'     => $status,
            'error'      => $error,
            'sent_at'    => now(),
            'created_at' => now(),
        ]);
    }
}
```

---

## `AlertMail` Mailable (Generic)

One mailable class handles all alert types — the view determines the content:

```php
final class AlertMail extends Mailable
{
    public function __construct(
        private readonly string $view,
        private readonly string $subject,
        public readonly Client $client,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subject)->view($this->view);
    }
}
```

---

## Monthly Report Email (With PDF Attachment)

```php
final class MonthlyReportMail extends Mailable
{
    public function __construct(
        private readonly Report $report,
        public readonly Client $client,
    ) {}

    public function build(): self
    {
        return $this
            ->subject("📊 {$this->report->period} — Your Website Report")
            ->view('emails.reports.monthly')
            ->attachData(
                app(BackblazeService::class)->download($this->report->b2_path),
                "ReviveGuard-Report-{$this->report->period}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}
```

---

## Email Layout (`resources/views/emails/layout.blade.php`)

```blade
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
             background:#f4f4f4;margin:0;padding:0;">
  <div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;">

    {{-- Header --}}
    <div style="background:#1a1a2e;padding:24px;text-align:center;">
      <span style="color:#fff;font-size:20px;font-weight:800;">ReviveGuard</span>
    </div>

    {{-- Body --}}
    <div style="padding:32px;">
      @yield('content')
    </div>

    {{-- Footer --}}
    <div style="background:#f9fafb;padding:24px;text-align:center;font-size:12px;color:#6b7280;">
      <p style="margin:0 0 4px;">ReviveGuard by WaybackRevive LLC</p>
      <p style="margin:0 0 8px;">You received this because you have an active website maintenance plan.</p>
      <a href="{{ config('app.portal_url') }}" style="color:#3b82f6;text-decoration:none;">
        Access your dashboard →
      </a>
    </div>

  </div>
</body>
</html>
```

### Email template rules:
- Plain language only — no technical jargon
- Single CTA button per email
- Inline CSS only (email clients block external stylesheets)
- Logo as text, not image (avoids image-blocking issues)

---

## Email Template File Structure

```
resources/views/emails/
├── layout.blade.php
├── alerts/
│   ├── site-down.blade.php
│   ├── site-recovered.blade.php
│   ├── ssl-expiry.blade.php
│   ├── domain-expiry.blade.php
│   └── update-complete.blade.php
├── billing/
│   └── payment-failed.blade.php
├── reports/
│   ├── monthly-ready.blade.php
│   └── monthly.blade.php          ← full report email body
├── tickets/
│   └── response.blade.php
└── welcome.blade.php
```

---

## Notification Scope (Phase 1)

**OUT — Do not build:**
- WhatsApp / SMS / Slack
- Client-configurable notification preferences
- Snooze or acknowledge alerts
- Escalation policies or on-call routing
- Notification digest (send immediately every time)
- Push notifications

---

## Definition of Done

```
[ ] site_down email reaches client inbox within 2 minutes of detection
[ ] site_down also emails admin (config('mail.admin_address'))
[ ] site_recovered email sent on recovery
[ ] SSL warning email sent at 30-day threshold (not again for same threshold)
[ ] Domain warning email sent at 30-day threshold
[ ] Monthly report email includes PDF as attachment
[ ] Update complete email lists plugins/core that were updated
[ ] Payment failed email sent to client on payment.failed webhook
[ ] Welcome email sent on new client activation
[ ] Every send attempt logged in notifications_log (success + failure)
[ ] Resend free tier (3,000/mo) not exceeded at MVP scale
[ ] No WhatsApp service, no WhatsApp env vars, no whatsapp_number column needed
[ ] Test email can be triggered from Filament admin (manual action)
[ ] APP_DEBUG=false in production — no stack traces in email bodies
```
