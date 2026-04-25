# SKILL: Client Portal (Livewire)

> Load this skill before building any portal screens.
> References: `07_CLIENT_PORTAL_SPEC.md`, `05_MVP_FEATURE_SPEC.md` Feature 8

---

## What This Covers
The complete client-facing portal at `portal.reviveguard.com` — all Livewire components, authentication (separate client guard), portal routes, and UX requirements.

---

## Tech Stack for This Component
- **Laravel Breeze** (Livewire stack) — handles login/forgot-password/reset-password
- **Livewire 3** — all portal components are Livewire
- **Tailwind CSS** — already included with Breeze
- **Alpine.js** — included with Livewire, use for minor interactivity
- **Guard:** `client` (NOT the default `web` guard — see `02_LARAVEL_FOUNDATION.md`)

---

## Route Configuration (`routes/web.php`)

```php
// Portal auth routes (Breeze generates these — customize for client guard)
Route::middleware('guest:client')->prefix('')->group(function () {
    Route::get('/login',            [ClientLoginController::class, 'create'])->name('login');
    Route::post('/login',           [ClientLoginController::class, 'store']);
    Route::get('/forgot-password',  [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

// Portal authenticated routes
Route::middleware(['auth:client', 'session.timeout'])->group(function () {
    Route::get('/',          fn() => redirect()->route('portal.dashboard'));
    Route::get('/dashboard', \App\Livewire\Portal\Dashboard::class)->name('portal.dashboard');
    Route::get('/events',    \App\Livewire\Portal\EventsList::class)->name('portal.events');
    Route::get('/reports',   \App\Livewire\Portal\ReportsList::class)->name('portal.reports');
    Route::get('/backups',   \App\Livewire\Portal\BackupsList::class)->name('portal.backups');
    Route::get('/tickets',   \App\Livewire\Portal\TicketsList::class)->name('portal.tickets');
    Route::get('/account',   \App\Livewire\Portal\AccountSettings::class)->name('portal.account');
    Route::post('/logout',   [ClientLoginController::class, 'destroy'])->name('logout');
    
    // Report PDF download — generates signed URL
    Route::get('/reports/{report}/download', [ReportDownloadController::class, 'show'])
         ->name('portal.reports.download');
});
```

### Session timeout middleware (8-hour timeout):
```php
final class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('client')->check()) {
            $lastActivity = session('last_activity_at', time());
            if (time() - $lastActivity > 28800) { // 8 hours
                auth('client')->logout();
                $request->session()->invalidate();
                return redirect()->route('login')->with('message', 'Your session expired. Please sign in again.');
            }
            session(['last_activity_at' => time()]);
        }
        return $next($request);
    }
}
```

---

## Portal Login (customised Breeze)

The Breeze login views must use the `client` guard. Key customization in `ClientLoginController`:

```php
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);
    
    // Rate limit: 5 attempts, then 60 second lockout
    $key = 'login.attempts.' . $request->ip();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please wait {$seconds} seconds.",
        ]);
    }
    
    if (!auth('client')->attempt([
        'email'    => $request->email,
        'password' => $request->password,
    ], $request->boolean('remember'))) {
        RateLimiter::hit($key, 60);
        throw ValidationException::withMessages([
            'email' => 'Invalid email or password.',
        ]);
    }
    
    RateLimiter::clear($key);
    $request->session()->regenerate();
    
    // Update last login
    auth('client')->user()->update(['portal_last_login' => now()]);
    
    return redirect()->intended(route('portal.dashboard'));
}
```

---

## Livewire Component: `Dashboard`

```php
final class Dashboard extends Component
{
    public ?Site $site = null;
    
    // Livewire polling — refresh every 60 seconds
    #[Polling(interval: 60000)]
    public function boot(): void {}
    
    public function mount(): void
    {
        // Phase 1: Client has one site — get the first one
        $this->site = auth('client')->user()
            ->sites()
            ->with(['plan'])
            ->first();
    }
    
    public function render(): View
    {
        if (!$this->site) {
            return view('livewire.portal.dashboard-no-site');
        }
        
        $recentEvents = Event::where('site_id', $this->site->id)
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get();
        
        return view('livewire.portal.dashboard', [
            'site'         => $this->site,
            'recentEvents' => $recentEvents,
            'sslDays'      => $this->site->ssl_expires_at
                ? now()->diffInDays($this->site->ssl_expires_at, false) : null,
            'domainDays'   => $this->site->domain_expires_at
                ? now()->diffInDays($this->site->domain_expires_at, false) : null,
            'lastBackup'   => Backup::where('site_id', $this->site->id)
                ->where('status', BackupStatus::SUCCESS)
                ->latest('created_at')->first(),
        ]);
    }
}
```

### Dashboard view (`livewire/portal/dashboard.blade.php`) — key elements:

```blade
{{-- Status card --}}
<div class="status-card {{ $site->status === 'active' ? 'status-card--up' : 'status-card--down' }}">
    <span class="status-pill">
        {{ $site->status === 'active' ? '● SITE IS UP' : '✗ SITE IS DOWN' }}
    </span>
    <div>{{ $site->domain }}</div>
    <div class="text-sm text-gray-400">
        Checked {{ $site->last_seen_at?->diffForHumans() ?? 'Never' }}
    </div>
</div>

{{-- Uptime card --}}
<div class="metric-card">
    <div class="metric-value">{{ $site->uptime_30d ? number_format($site->uptime_30d, 2) . '%' : '—' }}</div>
    <div class="metric-label">Uptime — Last 30 days</div>
</div>

{{-- SSL card — amber warning if < 30 days --}}
<div class="metric-card {{ $sslDays !== null && $sslDays < 30 ? 'metric-card--warning' : '' }}">
    <div class="metric-value">
        @if($sslDays === null) Unknown
        @elseif($sslDays < 0) Expired
        @else {{ $sslDays }} days
        @endif
    </div>
    <div class="metric-label">SSL Certificate</div>
</div>

{{-- Recent activity --}}
@foreach($recentEvents as $event)
    <div class="event-row" wire:click="showEvent('{{ $event->id }}')">
        <span class="event-icon event-icon--{{ $event->severity->value }}">
            @if($event->severity === \App\Enums\EventSeverity::SUCCESS) ✓
            @elseif($event->severity === \App\Enums\EventSeverity::WARNING) ⚠
            @elseif($event->severity === \App\Enums\EventSeverity::CRITICAL) ✗
            @else ℹ
            @endif
        </span>
        <span>{{ $event->title }}</span>
        <span class="text-sm text-gray-400">{{ $event->occurred_at->format('M j, g:i a') }}</span>
    </div>
@endforeach
```

---

## Livewire Component: `EventsList`

```php
final class EventsList extends Component
{
    public string $filterType     = 'all';
    public string $filterSeverity = 'all';
    public string $filterPeriod   = '30';
    
    public function render(): View
    {
        $client = auth('client')->user();
        $siteIds = $client->sites()->pluck('id');
        
        $query = Event::whereIn('site_id', $siteIds)->orderByDesc('occurred_at');
        
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }
        
        if ($this->filterSeverity !== 'all') {
            $query->where('severity', $this->filterSeverity);
        }
        
        if ($this->filterPeriod !== 'all') {
            $query->where('occurred_at', '>=', now()->subDays((int) $this->filterPeriod));
        }
        
        return view('livewire.portal.events-list', [
            'events' => $query->paginate(20),
        ]);
    }
}
```

---

## Report PDF Download

Never expose a permanent URL to a B2 file. Generate a temporary signed URL on each download request:

```php
final class ReportDownloadController extends Controller
{
    public function show(Report $report): RedirectResponse
    {
        // Authorization: ensure this report belongs to the authenticated client
        $client = auth('client')->user();
        abort_unless($client->sites()->where('id', $report->site_id)->exists(), 403);
        
        // Generate temporary B2 signed URL (1 hour TTY)
        $signedUrl = app(BackblazeService::class)->getSignedUrl(
            path: $report->b2_path,
            expiresInSeconds: 3600
        );
        
        return redirect($signedUrl);
    }
}
```

---

## Livewire Component: `TicketsList`

```php
// Submit ticket action
public function submitTicket(): void
{
    $this->validate([
        'subject'     => 'required|string|max:255',
        'description' => 'required|string|max:5000',
    ]);
    
    $client = auth('client')->user();
    
    // Enforce plan limit (Monitor: 0 tickets/month, Guard: 1/month, Shield: unlimited)
    $planFeatures     = $client->activePlan()?->features ?? [];
    $monthlyLimit     = $planFeatures['support_tickets_per_month'] ?? 0;
    
    if ($monthlyLimit === 0) {
        $this->addError('subject', 'Your plan does not include support tickets. Please upgrade to Guard or Shield.');
        return;
    }
    
    $usedThisMonth = Ticket::where('client_id', $client->id)
        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->count();
    
    if ($monthlyLimit > 0 && $usedThisMonth >= $monthlyLimit) {
        $this->addError('subject', "You've used your {$monthlyLimit} support ticket(s) this month.");
        return;
    }
    
    Ticket::create([
        'tenant_id'   => $client->tenant_id,
        'client_id'   => $client->id,
        'site_id'     => $this->selectedSiteId,
        'subject'     => $this->subject,
        'description' => $this->description,
        'status'      => 'open',
    ]);
    
    $this->reset(['subject', 'description']);
    $this->dispatch('ticket-submitted');
}
```

---

## UX Requirements (Non-Negotiable)

### Language & Tone
- **No technical jargon** in any user-facing string
- "WordPress core update" → "Website software updated"
- "Heartbeat missed" → "We lost contact with your site"
- "CRON job" → never shown to client
- "SSL expires in 7 days" → "Your security certificate needs renewal in 7 days — this affects whether browsers trust your site"

### Status Colors (consistent across all components)
```css
/* Green: site up, success, all clear */
.status--success { color: #16a34a; background: #f0fdf4; }
/* Amber: warnings, expiry approaching */
.status--warning { color: #d97706; background: #fffbeb; }
/* Red: site down, critical, errors */
.status--critical { color: #dc2626; background: #fef2f2; }
/* Blue: informational */
.status--info { color: #2563eb; background: #eff6ff; }
/* Grey: pending, no data */
.status--neutral { color: #6b7280; background: #f9fafb; }
```

---

## Phase 1 Portal Scope Reminder

**OUT — Do not build:**
- Real-time push (WebSockets/SSE) — 60s polling is enough
- Multiple-site dashboard overview — click into each site separately
- Client-customizable notification preferences
- Chat widget
- Invoice history in portal (link to Stripe customer portal instead)
- Self-registration

---

## Definition of Done

```
[ ] Portal login works with client email/password (not admin credentials)
[ ] Admin login does NOT work on portal.reviveguard.com
[ ] Forgot password email sends and allows password reset
[ ] After 5 failed logins: 60-second lockout with countdown
[ ] Dashboard loads in < 2 seconds with real site data
[ ] Status card shows green for UP, red for DOWN
[ ] Uptime % shows correctly (from sites.uptime_30d)
[ ] SSL days shows with amber warning when < 30 days
[ ] Dashboard auto-refreshes every 60 seconds via Livewire polling
[ ] Events list: paginated, filters work (type, severity, period)
[ ] Report PDF download: generates fresh signed URL, never stores permanent URL
[ ] Support ticket: Monitor plan client cannot submit (enforced)
[ ] Support ticket: Guard client can submit 1/month (enforced)
[ ] Account settings: change name, email, WhatsApp number, password
[ ] Client cannot see another client's data (tenancy scoping)
[ ] All pages mobile-responsive
[ ] No PHP/version/path info in error messages (APP_DEBUG=false in production)
[ ] Session expires after 8 hours of inactivity
```
