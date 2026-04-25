# SKILL: Agent API Endpoints

> Load this skill before building any `/api/v1/agent/*` routes.
> References: `04_API_DESIGN.md`, `05_MVP_FEATURE_SPEC.md`, `06_AGENT_PLUGIN_SPEC.md`

---

## What This Covers
All four agent API endpoints, the `AgentTokenAuth` middleware, rate limiting, heartbeat processing, command delivery, plugin list ingestion, and the job pipeline behind them.

---

## Route Structure (`routes/api.php`)

```php
Route::prefix('v1')->group(function () {
    // Agent endpoints — token auth, rate limited
    Route::middleware(['agent.token.auth', 'throttle:agent'])
        ->prefix('agent')
        ->group(function () {
            Route::post('/heartbeat',       [HeartbeatController::class, 'store']);
            Route::post('/command-result',  [CommandResultController::class, 'store']);
            Route::post('/plugin-list',     [PluginListController::class, 'store']);
            Route::post('/event',           [AgentEventController::class, 'store']);
        });

    // Webhook endpoints — separate auth per webhook type
    Route::prefix('webhooks')->group(function () {
        Route::post('/uptime-kuma', [UptimeKumaWebhookController::class, 'handle'])
            ->middleware('verify.uptime-kuma-webhook');
        // Stripe webhook is handled by Cashier's built-in route
    });
});
```

---

## Middleware: `AgentTokenAuth`

```php
final class AgentTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $rawToken = substr($authHeader, 7);
        
        if (empty($rawToken) || strlen($rawToken) < 32) {
            return response()->json(['error' => 'Invalid token format'], 401);
        }
        
        $hashedToken = hash('sha256', $rawToken);
        
        $site = Site::where('agent_token', $hashedToken)
                    ->where('status', '!=', SiteStatus::SUSPENDED)
                    ->first();
        
        if (!$site) {
            // Use identical timing to prevent token enumeration
            hash('sha256', bin2hex(random_bytes(32)));
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Inject site into request for use in controllers
        $request->attributes->set('site', $site);
        
        return $next($request);
    }
}
```

**Register in `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'agent.token.auth'            => AgentTokenAuth::class,
        'verify.uptime-kuma-webhook'  => VerifyUptimeKumaWebhook::class,
    ]);
})
```

---

## Rate Limiting

In `AppServiceProvider::boot()`:
```php
RateLimiter::for('agent', function (Request $request) {
    $site = $request->attributes->get('site');
    return [
        Limit::perMinute(60)->by($site?->id ?? $request->ip())
             ->response(fn() => response()->json(['error' => 'Too many requests'], 429)),
    ];
});
```

> **60 requests/minute** = 1/second max. Normal heartbeat = 1 per 5 min. This gives ample room for retries without allowing abuse.

---

## POST `/api/v1/agent/heartbeat`

### Controller:
```php
final class HeartbeatController extends Controller
{
    public function store(HeartbeatRequest $request): JsonResponse
    {
        $site = $request->attributes->get('site');
        $data = $request->validated();
        
        // Dispatch async job — do NOT process synchronously on this request
        ProcessHeartbeat::dispatch($site, $data);
        
        // Return any pending commands immediately (this is the critical path)
        $commands = SiteCommand::where('site_id', $site->id)
                               ->where('status', CommandStatus::PENDING)
                               ->orderBy('queued_at')
                               ->limit(5)
                               ->get();
        
        // Mark commands as sent
        $commands->each(fn($cmd) => $cmd->update([
            'status'  => CommandStatus::SENT,
            'sent_at' => now(),
        ]));
        
        return response()->json([
            'status'      => 'ok',
            'server_time' => now()->toIso8601String(),
            'commands'    => $commands->map(fn($cmd) => [
                'id'     => $cmd->id,
                'type'   => $cmd->type->value,
                'params' => $cmd->params,
            ]),
        ]);
    }
}
```

### `HeartbeatRequest` validation:
```php
public function rules(): array
{
    return [
        'timestamp'    => 'required|date',
        'site_url'     => 'required|url|max:500',
        'wp_version'   => 'nullable|string|max:20',
        'php_version'  => 'nullable|string|max:20',
        'plugin_count' => 'nullable|integer|min:0|max:1000',
        'theme_name'   => 'nullable|string|max:255',
        'disk_usage_mb'=> 'nullable|integer|min:0',
        'debug_mode'   => 'nullable|boolean',
        'agent_version'=> 'nullable|string|max:20',
    ];
}
```

### Job: `ProcessHeartbeat`
```php
final class ProcessHeartbeat implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public string $queue = 'default';
    
    public function __construct(
        private readonly Site $site,
        private readonly array $data,
    ) {}
    
    public function uniqueId(): string
    {
        return $this->site->id;
    }
    
    public function handle(HeartbeatService $service): void
    {
        $service->process($this->site, $this->data);
    }
}
```

### `HeartbeatService::process()`:
```php
public function process(Site $site, array $data): void
{
    $wasDown = $site->status === SiteStatus::DOWN;
    
    $site->update([
        'status'       => SiteStatus::ACTIVE,
        'last_seen_at' => now(),
        'wp_version'   => $data['wp_version'] ?? $site->wp_version,
        'php_version'  => $data['php_version'] ?? $site->php_version,
        'plugin_count' => $data['plugin_count'] ?? $site->plugin_count,
        'theme_name'   => $data['theme_name'] ?? $site->theme_name,
        'disk_usage_mb'=> $data['disk_usage_mb'] ?? $site->disk_usage_mb,
        'debug_mode'   => $data['debug_mode'] ?? $site->debug_mode,
        'agent_version'=> $data['agent_version'] ?? $site->agent_version,
    ]);
    
    // If site was down, create a recovery event and alert
    if ($wasDown) {
        Event::create([
            'tenant_id'   => $site->tenant_id,
            'site_id'     => $site->id,
            'type'        => 'site_recovered',
            'severity'    => EventSeverity::SUCCESS,
            'title'       => 'Site is back online',
            'description' => 'Site recovered — heartbeat received after downtime.',
            'occurred_at' => now(),
        ]);
        
        SendAlert::dispatch($site, 'site_recovered')->onQueue('critical');
    }
}
```

### Scheduler: `CheckMissedHeartbeats` (every 5 min)
```php
// In Console/Kernel.php or Schedule::call():
Schedule::job(new CheckMissedHeartbeats())->everyFiveMinutes();

// CheckMissedHeartbeats job:
public function handle(): void
{
    $threshold = now()->subMinutes(6); // 5min interval + 1min grace
    
    Site::where('status', SiteStatus::ACTIVE)
        ->where('last_seen_at', '<', $threshold)
        ->where('site_type', SiteType::WORDPRESS) // heartbeat-based only
        ->chunk(100, function ($sites) {
            foreach ($sites as $site) {
                $site->update(['status' => SiteStatus::DOWN]);
                
                Event::create([
                    'tenant_id'   => $site->tenant_id,
                    'site_id'     => $site->id,
                    'type'        => 'site_down',
                    'severity'    => EventSeverity::CRITICAL,
                    'title'       => 'Site appears to be down',
                    'description' => 'No heartbeat received for over 6 minutes.',
                    'occurred_at' => now(),
                ]);
                
                SendAlert::dispatch($site, 'site_down')->onQueue('critical');
            }
        });
}
```

---

## POST `/api/v1/agent/command-result`

### Controller:
```php
final class CommandResultController extends Controller
{
    public function store(CommandResultRequest $request): JsonResponse
    {
        $site = $request->attributes->get('site');
        $data = $request->validated();
        
        $command = SiteCommand::where('id', $data['command_id'])
                              ->where('site_id', $site->id)
                              ->firstOrFail();
        
        // Prevent replay attacks — only accept results for sent/executing commands
        if (!in_array($command->status, [CommandStatus::SENT, CommandStatus::EXECUTING])) {
            return response()->json(['status' => 'already_processed']);
        }
        
        ProcessCommandResult::dispatch($command, $data);
        
        return response()->json(['status' => 'received']);
    }
}
```

### `ProcessCommandResult` job handles:
- `run_backup`: create/update `Backup` record, log event, notify client
- `run_wp_updates`: log event with plugins updated, notify client

---

## POST `/api/v1/agent/plugin-list`

### Controller:
```php
final class PluginListController extends Controller
{
    public function store(PluginListRequest $request): JsonResponse
    {
        $site = $request->attributes->get('site');
        
        // Store as a snapshot — do not update in real-time on this thread
        StorePluginSnapshot::dispatch($site, $request->validated('plugins'));
        
        return response()->json([
            'status'         => 'received',
            'plugins_logged' => count($request->validated('plugins')),
        ]);
    }
}
```

### `PluginListRequest` validation:
```php
'plugins'                          => 'required|array|max:500',
'plugins.*.slug'                   => 'required|string|max:200',
'plugins.*.name'                   => 'required|string|max:255',
'plugins.*.version'                => 'required|string|max:50',
'plugins.*.latest_version'         => 'nullable|string|max:50',
'plugins.*.is_active'              => 'required|boolean',
'plugins.*.update_available'       => 'required|boolean',
```

---

## POST `/api/v1/agent/event`

### Controller:
```php
final class AgentEventController extends Controller
{
    public function store(AgentEventRequest $request): JsonResponse
    {
        $site = $request->attributes->get('site');
        $data = $request->validated();
        
        $event = Event::create([
            'tenant_id'   => $site->tenant_id,
            'site_id'     => $site->id,
            'type'        => $data['type'],
            'severity'    => $data['severity'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'metadata'    => $data['metadata'] ?? [],
            'occurred_at' => $data['occurred_at'],
        ]);
        
        return response()->json([
            'status'   => 'logged',
            'event_id' => $event->id,
        ]);
    }
}
```

### Allowed event types (whitelist — never accept arbitrary strings):
```php
private const ALLOWED_EVENT_TYPES = [
    'php_error_spike',
    'disk_usage_warning',
    'file_change_detected',
    'backup_started',
    'update_started',
];

// Validate in AgentEventRequest:
'type' => ['required', 'string', Rule::in(self::ALLOWED_EVENT_TYPES)],
```

---

## Uptime Kuma Webhook: POST `/api/v1/webhooks/uptime-kuma`

### Verification middleware:
```php
final class VerifyUptimeKumaWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-Webhook-Secret');
        
        if (!$secret || !hash_equals(config('services.uptime_kuma.webhook_secret'), $secret)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        return $next($request);
    }
}
```

### Controller:
```php
public function handle(Request $request): JsonResponse
{
    $monitorId = $request->input('monitor.id');
    $status    = $request->input('heartbeat.status'); // 0=down, 1=up
    
    $site = Site::where('uptime_kuma_monitor_id', $monitorId)->first();
    
    if (!$site) {
        return response()->json(['status' => 'monitor_not_found']);
    }
    
    ProcessUptimeKumaWebhook::dispatch($site, $status, $request->all());
    
    return response()->json(['status' => 'received']);
}
```

---

## Horizon Queue Configuration (`config/horizon.php`)

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'maxProcesses'  => 10,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'queue' => ['critical', 'default'],
            'sleep' => 0,
            'timeout' => 300,
        ],
    ],
],
```

---

## Definition of Done

```
[ ] POST /api/v1/agent/heartbeat: returns 200 + empty commands array for new site
[ ] Auth: request without Bearer token returns 401
[ ] Auth: request with wrong token returns 401 (same response time as valid token)
[ ] Rate limit: 61st request per minute returns 429
[ ] Heartbeat received → ProcessHeartbeat job dispatched → site.last_seen_at updated
[ ] Stop sending heartbeat → after 6 min → site.status = 'down' + event logged
[ ] Resume heartbeat → site.status = 'active' + recovery event logged
[ ] Command queued in admin → received in next heartbeat response
[ ] Command result received → Backup/Event record created
[ ] Plugin list received → PluginSnapshot created
[ ] Uptime Kuma webhook (status=0) → site marked down → alert dispatched
[ ] Uptime Kuma webhook (status=1) → site marked up → recovery alert dispatched
[ ] Agent event with unknown type → rejected with 422
[ ] All controllers return JSON, never HTML
```
