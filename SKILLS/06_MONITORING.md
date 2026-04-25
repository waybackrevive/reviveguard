# SKILL: Monitoring — Uptime, SSL, Domain Expiry

> Load this skill before building any monitoring/scheduler components.
> References: `05_MVP_FEATURE_SPEC.md` Features 4 & 5, `04_API_DESIGN.md`

---

## What This Covers
All scheduled monitoring jobs: SSL expiry checks, domain WHOIS expiry checks, Uptime Kuma stats sync, and the missed heartbeat detector. Also covers Uptime Kuma integration and how uptime data flows into the system.

---

## Scheduler Registration (`routes/console.php` or `App\Console\Kernel`)

```php
Schedule::job(new CheckMissedHeartbeats())->everyFiveMinutes();
Schedule::job(new CheckSslExpiry())->dailyAt('06:00');
Schedule::job(new CheckDomainExpiry())->dailyAt('07:00');
Schedule::job(new UpdateUptimeStats())->everySixHours();
Schedule::job(new GenerateMonthlyReports())->monthlyOn(1, '09:00');
Schedule::job(new TriggerScheduledBackups())->hourly(); // Checks which sites need backup
```

---

## Job: `CheckSslExpiry`

```php
final class CheckSslExpiry implements ShouldQueue
{
    public int $tries    = 2;
    public string $queue = 'default';
    
    public function handle(): void
    {
        Site::where('status', '!=', SiteStatus::SUSPENDED)
            ->whereNotNull('url')
            ->chunk(50, function ($sites) {
                foreach ($sites as $site) {
                    try {
                        $this->checkSite($site);
                    } catch (Throwable $e) {
                        Log::warning("SSL check failed for site {$site->id}: " . $e->getMessage());
                    }
                }
            });
    }
    
    private function checkSite(Site $site): void
    {
        $host   = parse_url($site->url, PHP_URL_HOST);
        $port   = 443;
        $timeout = 10;
        
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ],
        ]);
        
        $stream = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$stream) {
            // SSL check failed — log warning, don't mark site down (Uptime Kuma handles that)
            $site->update(['ssl_valid' => false]);
            $this->logSslEvent($site, 'ssl_check_failed', EventSeverity::WARNING,
                "SSL check failed: {$errstr}");
            return;
        }
        
        $params = stream_context_get_params($stream);
        $cert   = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        fclose($stream);
        
        if (!$cert || !isset($cert['validTo_time_t'])) {
            return;
        }
        
        $expiresAt    = Carbon::createFromTimestamp($cert['validTo_time_t']);
        $daysLeft     = now()->diffInDays($expiresAt, false);
        $issuer       = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Unknown');
        
        $site->update([
            'ssl_expires_at' => $expiresAt->toDateString(),
            'ssl_issuer'     => $issuer,
            'ssl_valid'      => $daysLeft > 0,
        ]);
        
        // Alert thresholds — only alert ONCE per threshold crossing
        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchSslAlert($site, $daysLeft, $threshold);
                break;
            }
        }
    }
    
    private function dispatchSslAlert(Site $site, int $daysLeft, int $threshold): void
    {
        // Check if we already sent an alert for this threshold this year
        $alreadySent = Event::where('site_id', $site->id)
            ->where('type', 'ssl_expiry_warning')
            ->where('metadata->threshold', $threshold)
            ->where('occurred_at', '>=', now()->subDays($threshold + 5))
            ->exists();
        
        if ($alreadySent) return;
        
        $event = Event::create([
            'tenant_id'   => $site->tenant_id,
            'site_id'     => $site->id,
            'type'        => 'ssl_expiry_warning',
            'severity'    => $daysLeft <= 7 ? EventSeverity::CRITICAL : EventSeverity::WARNING,
            'title'       => "SSL certificate expires in {$daysLeft} days",
            'description' => "SSL certificate for {$site->domain} expires on {$site->ssl_expires_at}. " .
                             "Action required to prevent site becoming inaccessible.",
            'metadata'    => ['days_left' => $daysLeft, 'threshold' => $threshold],
            'occurred_at' => now(),
        ]);
        
        SendAlert::dispatch($site, 'ssl_expiry_warning', ['days_left' => $daysLeft])
                 ->onQueue('default');
    }
}
```

---

## Job: `CheckDomainExpiry`

```php
final class CheckDomainExpiry implements ShouldQueue
{
    public int $tries    = 2;
    public string $queue = 'default';
    
    public function handle(): void
    {
        Site::where('status', '!=', SiteStatus::SUSPENDED)
            ->whereNotNull('domain')
            ->chunk(20, function ($sites) {
                foreach ($sites as $site) {
                    try {
                        $this->checkDomain($site);
                    } catch (Throwable $e) {
                        Log::warning("Domain check failed for {$site->domain}: " . $e->getMessage());
                    }
                    
                    // WHOIS servers rate-limit — sleep between requests
                    sleep(2);
                }
            });
    }
    
    private function checkDomain(Site $site): void
    {
        $whois = new \Iodev\Whois\Factory::get()->createWhois();
        
        try {
            $info = $whois->loadDomainInfo($site->domain);
        } catch (Throwable $e) {
            Log::info("WHOIS lookup failed for {$site->domain}: " . $e->getMessage());
            return; // Not all TLDs support WHOIS — skip silently
        }
        
        if (!$info || !$info->expirationDate) {
            return;
        }
        
        $expiresAt = Carbon::createFromTimestamp($info->expirationDate);
        $daysLeft  = now()->diffInDays($expiresAt, false);
        $registrar = $info->registrar ?? null;
        
        $site->update([
            'domain_expires_at' => $expiresAt->toDateString(),
            'registrar'         => $registrar,
        ]);
        
        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchDomainAlert($site, $daysLeft, $threshold);
                break;
            }
        }
    }
    
    private function dispatchDomainAlert(Site $site, int $daysLeft, int $threshold): void
    {
        $alreadySent = Event::where('site_id', $site->id)
            ->where('type', 'domain_expiry_warning')
            ->where('metadata->threshold', $threshold)
            ->where('occurred_at', '>=', now()->subDays($threshold + 5))
            ->exists();
        
        if ($alreadySent) return;
        
        Event::create([
            'tenant_id'   => $site->tenant_id,
            'site_id'     => $site->id,
            'type'        => 'domain_expiry_warning',
            'severity'    => $daysLeft <= 7 ? EventSeverity::CRITICAL : EventSeverity::WARNING,
            'title'       => "Domain expires in {$daysLeft} days",
            'description' => "Domain {$site->domain} expires on {$site->domain_expires_at}. " .
                             "Renew your domain to prevent the website going offline.",
            'metadata'    => ['days_left' => $daysLeft, 'threshold' => $threshold],
            'occurred_at' => now(),
        ]);
        
        SendAlert::dispatch($site, 'domain_expiry_warning', ['days_left' => $daysLeft])
                 ->onQueue('default');
    }
}
```

---

## Job: `UpdateUptimeStats` (every 6 hours)

```php
final class UpdateUptimeStats implements ShouldQueue
{
    public function handle(UptimeKumaService $kumaService): void
    {
        $kumaService->login(); // Refresh JWT token
        
        Site::whereNotNull('uptime_kuma_monitor_id')
            ->where('status', '!=', SiteStatus::SUSPENDED)
            ->chunk(50, function ($sites) use ($kumaService) {
                foreach ($sites as $site) {
                    try {
                        $uptime30 = $kumaService->getUptimePercent($site->uptime_kuma_monitor_id, 30);
                        $uptime7  = $kumaService->getUptimePercent($site->uptime_kuma_monitor_id, 7);
                        
                        $site->update([
                            'uptime_30d' => $uptime30,
                            'uptime_7d'  => $uptime7,
                        ]);
                    } catch (Throwable $e) {
                        Log::warning("Uptime stats fetch failed for site {$site->id}: " . $e->getMessage());
                    }
                }
            });
    }
}
```

---

## UptimeKumaService — Full Implementation

```php
final class UptimeKumaService
{
    private string $baseUrl;
    private string $token = '';
    
    public function __construct()
    {
        $this->baseUrl = config('services.uptime_kuma.url', 'http://127.0.0.1:3001');
    }
    
    public function login(): void
    {
        $response = Http::timeout(10)->post("{$this->baseUrl}/api/v1/login", [
            'username' => config('services.uptime_kuma.username'),
            'password' => config('services.uptime_kuma.password'),
        ]);
        
        if (!$response->successful()) {
            throw new RuntimeException('Uptime Kuma login failed: ' . $response->status());
        }
        
        $this->token = $response->json('token');
    }
    
    public function addMonitor(Site $site): int
    {
        $response = Http::withToken($this->token)
            ->timeout(10)
            ->post("{$this->baseUrl}/api/v1/monitors", [
                'type'            => 'http',
                'name'            => $site->name . ' (' . $site->domain . ')',
                'url'             => $site->url,
                'interval'        => 60, // seconds
                'retryInterval'   => 20,
                'maxretries'      => 2,
                'notificationIDList' => [],
            ]);
        
        if (!$response->successful()) {
            throw new RuntimeException('Failed to create Uptime Kuma monitor');
        }
        
        return $response->json('monitorID');
    }
    
    public function removeMonitor(int $monitorId): void
    {
        Http::withToken($this->token)
            ->timeout(10)
            ->delete("{$this->baseUrl}/api/v1/monitors/{$monitorId}");
    }
    
    public function getUptimePercent(int $monitorId, int $days = 30): float
    {
        $hours    = $days * 24;
        $response = Http::withToken($this->token)
            ->timeout(10)
            ->get("{$this->baseUrl}/api/v1/monitors/{$monitorId}/uptime/{$hours}");
        
        if (!$response->successful()) {
            return 0.0;
        }
        
        $data = $response->json();
        return round(($data['uptime'] ?? 0) * 100, 2);
    }
    
    public function configureWebhook(string $webhookUrl, string $secret): void
    {
        // Configure Uptime Kuma to send webhooks to your platform
        // This is done once during setup, not per-monitor
        Http::withToken($this->token)->post("{$this->baseUrl}/api/v1/notifications", [
            'name'   => 'ReviveGuard Platform',
            'type'   => 'webhook',
            'config' => [
                'webhookURL'           => $webhookUrl,
                'webhookContentType'   => 'json',
                'webhookAdditionalHeaders' => json_encode([
                    'X-Webhook-Secret' => $secret,
                ]),
            ],
            'isDefault' => true,
            'applyExisting' => true,
        ]);
    }
}
```

**`config/services.php` additions:**
```php
'uptime_kuma' => [
    'url'            => env('UPTIME_KUMA_URL', 'http://127.0.0.1:3001'),
    'username'       => env('UPTIME_KUMA_USERNAME'),
    'password'       => env('UPTIME_KUMA_PASSWORD'),
    'webhook_secret' => env('UPTIME_KUMA_WEBHOOK_SECRET'),
],
```

---

## Monitoring Scope (Phase 1)

**IN:**
- HTTP uptime check via Uptime Kuma (1-min interval)
- Heartbeat as secondary check (5-min interval)
- SSL expiry: daily check, 3 thresholds (60/30/7 days)
- Domain expiry: daily check, 3 thresholds (60/30/7 days)
- Uptime % pulled from Kuma every 6 hours
- One alert per threshold (not daily repetition)

**OUT — Do not build:**
- Multi-location monitoring
- Custom check frequency per plan
- DNS record change monitoring
- HTTP response code/content monitoring
- Transaction/form submission checks
- Public status page

---

## Definition of Done

```
[ ] CheckSslExpiry runs daily — updates sites.ssl_expires_at for all active sites
[ ] SSL alert fires at 30-day threshold — event logged + email dispatched
[ ] SSL alert does NOT fire twice for same threshold in same period
[ ] CheckDomainExpiry runs daily — updates sites.domain_expires_at
[ ] Domain alert fires at correct thresholds
[ ] UpdateUptimeStats updates uptime_30d and uptime_7d every 6 hours
[ ] CheckMissedHeartbeats runs every 5 min — marks WP sites down after 6 min silence
[ ] Uptime Kuma webhook fires when test site goes offline
[ ] UptimeKumaService.addMonitor() called on site create — monitor_id stored
[ ] UptimeKumaService.removeMonitor() called on site delete
[ ] WHOIS failure for unsupported TLD: skipped silently (no error/alert)
[ ] SSL check for non-HTTPS site: handled gracefully
```
