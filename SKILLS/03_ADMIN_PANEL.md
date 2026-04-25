# SKILL: Filament Admin Panel

> Load this skill before building any Filament resources.
> References: `05_MVP_FEATURE_SPEC.md` Feature 1, `03_DATABASE_SCHEMA.md`

---

## What This Covers
Building the entire Filament v3 admin panel at `app.reviveguard.com/admin`. Internal-only. Covers all Filament Resources, Widgets, and custom actions in Phase 1 scope.

---

## Scope (Phase 1 — Do Not Exceed)

**IN scope:**
- ClientResource (full CRUD)
- SiteResource (CRUD + agent token generation + Uptime Kuma integration)
- EventResource (read-only, paginated list)
- BackupResource (read-only list + manual trigger action)
- TicketResource (list + reply action)
- Dashboard widget: site health overview
- Manual actions: trigger backup, trigger update, generate report, resend report email
- 2FA for admin login

**OUT scope (do not build):**
- Multi-user admin roles (you're sole admin in Phase 1)
- Audit log of admin actions
- Bulk operations
- Custom plan builder
- Reseller management

---

## Filament Panel Config (`AdminPanelProvider.php`)

```php
->id('admin')
->path('admin')
->domain('app.reviveguard.com')
->authGuard('web')
->login()
->profile()
->colors(['primary' => Color::Blue])
->navigationGroups([
    'Clients & Sites',
    'Monitoring',
    'Reports & Billing',
])
->widgets([
    Widgets\AccountWidget::class,
    Widgets\SiteHealthOverview::class,
])
->pages([
    Pages\Dashboard::class,
])
->resources([
    ClientResource::class,
    SiteResource::class,
    EventResource::class,
    BackupResource::class,
    TicketResource::class,
])
->plugins([
    \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(), // Phase 2 — skip for now
])
```

---

## ClientResource

### List columns:
- Name (sortable, searchable)
- Email (searchable)
- Plan (from related subscription → plan name, badge)
- Sites count (computed)
- Status badge (active=green, suspended=amber, churned=grey)
- Created at (sortable)

### Form fields (create/edit):
```php
Forms\Components\Section::make('Contact Information')
    ->schema([
        TextInput::make('name')->required()->maxLength(255),
        TextInput::make('email')->email()->required()->maxLength(255),
        TextInput::make('phone')->tel()->maxLength(50),
        TextInput::make('whatsapp_number')
            ->label('WhatsApp Number')
            ->helperText('E.164 format: +14155551234')
            ->maxLength(50)
            ->rule('regex:/^\+[1-9]\d{1,14}$/'),
        Select::make('timezone')
            ->options(timezone_identifiers_list())
            ->searchable()
            ->default('UTC'),
        Select::make('source')
            ->options([
                'waybackrevive_restored' => 'WaybackRevive Client (Restored)',
                'inbound'                => 'Inbound Lead',
                'referral'               => 'Referral',
            ]),
    ]),
Forms\Components\Section::make('Portal Access')
    ->schema([
        TextInput::make('portal_password')
            ->password()
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->dehydrated(fn ($state) => filled($state))
            ->required(fn (string $context) => $context === 'create')
            ->label('Portal Password'),
    ]),
```

### View page:
- Show client info
- Related sites table (embedded)
- Related subscriptions (embedded)

---

## SiteResource

This is the most important resource. Take care.

### List columns:
- Site name
- URL (clickable link, opens in new tab)
- Client name
- Status badge: UP (green) / DOWN (red) / PENDING (grey) / WARNING (amber)
- Last seen (human diff: "3 minutes ago")
- Plan name badge
- Agent version

### Create form:
```php
TextInput::make('name')->required(),
TextInput::make('url')
    ->url()->required()
    ->helperText('Must include https://')
    ->afterStateUpdated(function ($state, callable $set) {
        // Auto-extract domain from URL
        $parsed = parse_url($state);
        if (isset($parsed['host'])) {
            $set('domain', $parsed['host']);
        }
    }),
TextInput::make('domain')->required(),
Select::make('site_type')
    ->options(SiteType::class)
    ->default(SiteType::WORDPRESS)
    ->required(),
Select::make('client_id')
    ->relationship('client', 'name')
    ->searchable()
    ->required(),
Select::make('plan_id')
    ->relationship('plan', 'name')
    ->required(),
```

### On site creation — these MUST happen automatically:
1. Generate agent token (HMAC-SHA256 secret, 32 bytes)
2. Store hashed token in `sites.agent_token`
3. Store last 4 chars in `sites.agent_token_last4`
4. Show the raw token ONCE in a modal (never shown again)
5. Call `UptimeKumaService::addMonitor($site)` to create HTTP monitor in Kuma
6. Store returned `monitor_id` in `sites.uptime_kuma_monitor_id`

### Agent token generation service (`AgentTokenService`):
```php
final class AgentTokenService
{
    public function generate(Site $site): string
    {
        $rawToken = bin2hex(random_bytes(32));    // 64 char hex string
        $hashed   = hash('sha256', $rawToken);
        
        $site->update([
            'agent_token'      => $hashed,
            'agent_token_last4' => substr($rawToken, -4),
        ]);
        
        return $rawToken; // Return ONCE — never stored in plaintext
    }
    
    public function verify(string $rawToken, Site $site): bool
    {
        return hash_equals($site->agent_token, hash('sha256', $rawToken));
    }
}
```

### After create — show token modal:
```php
// In SiteResource CreateSite page:
protected function afterCreate(): void
{
    $rawToken = app(AgentTokenService::class)->generate($this->record);
    
    // Dispatch Uptime Kuma monitor creation
    CreateUptimeKumaMonitor::dispatch($this->record);
    
    // Flash the token for display in success notification
    Filament::notify('success', 'Site created. Copy the agent token now — it will not be shown again.');
    
    // Store in session for one-time display
    session()->flash('agent_token', $rawToken);
    session()->flash('agent_token_site', $this->record->name);
}
```

### Site view page:
- Status, last seen, SSL info, domain info
- Recent events table (last 10)
- Recent backups table (last 5)
- Manual action buttons:
  - "Trigger Backup" → queues `run_backup` SiteCommand
  - "Run Updates" → queues `run_wp_updates` SiteCommand (Guard/Shield only)
  - "Regenerate Agent Token" → confirmation modal → revokes old token

### Manual actions on Site:
```php
Action::make('triggerBackup')
    ->label('Trigger Backup')
    ->icon('heroicon-o-arrow-down-tray')
    ->requiresConfirmation()
    ->action(function (Site $record) {
        SiteCommand::create([
            'tenant_id' => $record->tenant_id,
            'site_id'   => $record->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::PENDING,
            'params'    => ['backup_type' => 'full', 'destination' => 'b2'],
        ]);
        Notification::make()->success()->title('Backup queued')->send();
    }),
```

---

## UptimeKumaService

```php
final class UptimeKumaService
{
    private string $baseUrl;
    private string $token; // JWT token from login
    
    public function login(): void
    {
        // POST /api/v1/login with username/password
        // Store JWT token for subsequent calls
    }
    
    public function addMonitor(Site $site): int
    {
        // POST /api/v1/monitors
        // Returns monitor ID — store in sites.uptime_kuma_monitor_id
        // Type: HTTP/HTTPS, interval: 60s
    }
    
    public function removeMonitor(int $monitorId): void
    {
        // DELETE /api/v1/monitors/{id}
        // Called when site is deleted
    }
    
    public function getUptimePercent(int $monitorId, int $days = 30): float
    {
        // GET /api/v1/monitors/{id}/uptime/{hours}
        // hours = days * 24
    }
}
```

---

## Dashboard Widget: SiteHealthOverview

```php
class SiteHealthOverview extends Widget
{
    protected static string $view = 'filament.widgets.site-health-overview';
    
    public function getStats(): array
    {
        return [
            Stat::make('Sites Up', Site::where('status', SiteStatus::ACTIVE)->count())
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Sites Down', Site::where('status', SiteStatus::DOWN)->count())
                ->color('danger')
                ->icon('heroicon-o-x-circle'),
            Stat::make('Warnings', Site::where('status', SiteStatus::WARNING)->count())
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Total Active Clients', Client::where('status', 'active')->count())
                ->icon('heroicon-o-users'),
        ];
    }
}
```

---

## EventResource

Read-only. No create/edit/delete from admin.

### Columns:
- Type (badge with color by severity)
- Site name (linked)
- Title
- Severity badge
- Occurred at (sorted desc)

### Filters:
- By site
- By severity
- By type
- By date range

---

## TicketResource

### Columns:
- Subject
- Client name
- Site name
- Status (open/in-progress/closed)
- Created at

### View action — reply form:
```php
Action::make('reply')
    ->form([
        Textarea::make('response_message')->required(),
        Select::make('status')
            ->options(['in-progress' => 'In Progress', 'closed' => 'Closed']),
    ])
    ->action(function (array $data, Ticket $record) {
        $record->update([
            'response_message' => $data['response_message'],
            'status'           => $data['status'],
            'responded_at'     => now(),
        ]);
        // Dispatch notification to client
        SendTicketReplyNotification::dispatch($record);
    }),
```

---

## Definition of Done

```
[ ] Admin login works with 2FA
[ ] ClientResource: create/edit/view/list all work
[ ] SiteResource: create generates token + creates Uptime Kuma monitor
[ ] Token displayed once in modal on site create
[ ] SiteResource: view shows status, events, backups
[ ] Manual backup trigger queues a SiteCommand record
[ ] Dashboard widget shows correct counts
[ ] EventResource: paginated, filterable, read-only
[ ] TicketResource: list + reply action works
[ ] All Filament resources are scoped to tenant (no cross-tenant data leak)
[ ] Filament protected: non-authenticated users get 403
[ ] No Vue/React components added (Filament's built-in only)
```
