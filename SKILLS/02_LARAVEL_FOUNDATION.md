# SKILL: Laravel Foundation — DB, Tenancy, Auth, Migrations

> Load this skill before building the Laravel app skeleton.
> References: `03_DATABASE_SCHEMA.md`, `02_SYSTEM_ARCHITECTURE.md`, `09_DEV_EXECUTION_PLAN.md`

---

## What This Covers
Creating the Laravel 11 application with all packages installed, all database migrations, stancl/tenancy configured in single-DB mode, both authentication guards (admin + portal client), and initial seed data.

---

## Required Composer Packages

```bash
composer require \
  filament/filament:"^3.2" \
  laravel/cashier:"^15.0" \
  stancl/tenancy:"^3.8" \
  laravel/horizon:"^5.21" \
  laravel/breeze:"^2.1" \
  iodev/whois:"^2.0" \
  resend/resend-php:"^0.13"

# Dev packages
composer require --dev \
  laravel/pail \
  laravel/tinker
```

### After package installs:
```bash
php artisan filament:install --panels
php artisan breeze:install livewire  # Livewire stack for portal
php artisan horizon:install
php artisan cashier:publish
```

---

## stancl/tenancy Configuration (CRITICAL — Read First)

**Mode:** Single database, shared tables. WaybackRevive is the only tenant in Phase 1.

### `config/tenancy.php` key settings:
```php
'tenant_model' => \App\Models\Tenant::class,
'id_generator' => \Stancl\Tenancy\UUIDGenerator::class,

// Single-DB mode: tenant_id column on shared tables
'bootstrappers' => [
    // Do NOT use DatabaseTenancyBootstrapper — that's for separate DBs
    // For single-DB, we use global scopes on models instead
],
```

**Important:** For single-DB mode, stancl/tenancy works via `InitializeTenancyByDomain` middleware and global query scopes. Every model that has a `tenant_id` column must use the `BelongsToTenant` trait.

### Trait usage on all tenant-scoped models:
```php
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Client extends Model
{
    use BelongsToTenant;
    // tenant_id scoping is automatic
}
```

Models with `BelongsToTenant`: `Client`, `Site`, `Plan`, `Subscription`, `Event`, `Backup`, `Report`, `Ticket`, `SiteCommand`, `PluginSnapshot`

Models WITHOUT this trait (central/unscoped): `Tenant`, `User`

---

## Migration Sequence (ORDER MATTERS)

Migrations must be created in this exact order to satisfy foreign key constraints:

```
0001_01_01_000000_create_tenants_table.php         ← Central, no tenant_id
0001_01_01_000001_create_users_table.php            ← Central, has tenant_id FK
0001_01_01_000002_create_plans_table.php            ← Tenant-scoped
0001_01_01_000003_create_clients_table.php          ← Tenant-scoped
0001_01_01_000004_create_subscriptions_table.php    ← Cashier-compatible
0001_01_01_000005_create_sites_table.php            ← Core entity
0001_01_01_000006_create_site_commands_table.php    ← Depends on sites
0001_01_01_000007_create_plugin_snapshots_table.php ← Depends on sites
0001_01_01_000008_create_events_table.php           ← Depends on sites
0001_01_01_000009_create_backups_table.php          ← Depends on sites
0001_01_01_000010_create_reports_table.php          ← Depends on sites + clients
0001_01_01_000011_create_tickets_table.php          ← Depends on clients + sites
0001_01_01_000012_create_notifications_log_table.php ← Standalone
```

---

## Key Migration Details

### `sites` table — critical columns to note:
```php
$table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
$table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
$table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
$table->foreignUuid('plan_id')->nullable()->constrained('plans');
$table->unsignedBigInteger('subscription_id')->nullable()
      ->references('id')->on('subscriptions');

// Agent
$table->string('agent_token', 255)->unique()->nullable(); // stored HASHED
$table->string('agent_token_last4', 4)->nullable();       // for display "****abc1"
$table->string('agent_version', 50)->nullable();
$table->timestamp('agent_installed_at')->nullable();

// Status
$table->string('status', 50)->default('pending');
// Values: 'pending', 'active', 'down', 'warning', 'suspended'
$table->timestamp('last_seen_at')->nullable();

// Monitoring data
$table->integer('uptime_kuma_monitor_id')->nullable();
$table->decimal('uptime_30d', 5, 2)->nullable();
$table->decimal('uptime_7d', 5, 2)->nullable();

// SSL
$table->date('ssl_expires_at')->nullable();
$table->string('ssl_issuer', 255)->nullable();
$table->boolean('ssl_valid')->nullable();

// Domain
$table->date('domain_expires_at')->nullable();
$table->string('registrar', 255)->nullable();

// WordPress metadata
$table->string('wp_version', 50)->nullable();
$table->string('php_version', 50)->nullable();
$table->integer('plugin_count')->nullable();
$table->string('theme_name', 255)->nullable();
$table->integer('disk_usage_mb')->nullable();
$table->boolean('debug_mode')->nullable();
```

### `site_commands` table:
```php
$table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
$table->foreignUuid('tenant_id')->constrained('tenants');
$table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
$table->string('type', 50);          // 'run_backup', 'run_wp_updates'
$table->string('status', 50)->default('pending');
// Values: 'pending', 'sent', 'executing', 'success', 'failed'
$table->jsonb('params')->default('{}');
$table->jsonb('result')->nullable();
$table->string('error_message')->nullable();
$table->timestamp('queued_at')->useCurrent();
$table->timestamp('sent_at')->nullable();
$table->timestamp('completed_at')->nullable();
$table->timestamps();
```

---

## Authentication Setup

### Two completely separate auth guards:

**Guard 1 — Admin (Filament)**
- Uses `users` table (your team)
- Path: `app.reviveguard.com/admin`
- Guard name: `web` (Laravel default, repurposed)
- 2FA via `laravel/fortify` or Filament's built-in 2FA

**Guard 2 — Portal Client**
- Uses `clients` table  
- Path: `portal.reviveguard.com`
- Guard name: `client`
- No 2FA in Phase 1 (MVP)

### `config/auth.php` additions:
```php
'guards' => [
    'web' => [  // admin guard (default)
        'driver' => 'session',
        'provider' => 'users',
    ],
    'client' => [  // portal guard
        'driver' => 'session',
        'provider' => 'clients',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'clients' => [
        'driver' => 'eloquent',
        'model' => App\Models\Client::class,
    ],
],
```

### `Client` model must implement `Authenticatable`:
```php
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use BelongsToTenant;
    
    protected $hidden = ['portal_password', 'remember_token'];
    
    // Map Breeze's expected field name
    public function getAuthPassword(): string
    {
        return $this->portal_password;
    }
}
```

### Filament panel — admin guard setup (`app/Providers/Filament/AdminPanelProvider.php`):
```php
->authGuard('web')
->login()
->path('admin')
->domain('app.reviveguard.com')
```

### Portal routes (`routes/web.php`) — use `client` guard middleware:
```php
Route::middleware(['auth:client'])->prefix('')->group(function () {
    Route::get('/dashboard', [PortalDashboardController::class, 'index']);
    // ... all portal routes
});
```

---

## Enums (create in `app/Enums/`)

```php
// SiteStatus.php
enum SiteStatus: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case DOWN      = 'down';
    case WARNING   = 'warning';
    case SUSPENDED = 'suspended';
}

// CommandType.php
enum CommandType: string
{
    case RUN_BACKUP     = 'run_backup';
    case RUN_WP_UPDATES = 'run_wp_updates';
}

// CommandStatus.php
enum CommandStatus: string
{
    case PENDING   = 'pending';
    case SENT      = 'sent';
    case EXECUTING = 'executing';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';
}

// EventSeverity.php
enum EventSeverity: string
{
    case SUCCESS  = 'success';
    case INFO     = 'info';
    case WARNING  = 'warning';
    case CRITICAL = 'critical';
}

// BackupStatus.php
enum BackupStatus: string
{
    case PENDING  = 'pending';
    case RUNNING  = 'running';
    case SUCCESS  = 'success';
    case FAILED   = 'failed';
    case EXPIRED  = 'expired';
}

// SiteType.php
enum SiteType: string
{
    case WORDPRESS = 'wordpress';
    case HTML      = 'html';
    case OTHER     = 'other';
}
```

---

## Seeders

### `TenantSeeder.php`:
```php
Tenant::create([
    'id'            => '00000000-0000-0000-0000-000000000001',
    'name'          => 'WaybackRevive',
    'slug'          => 'waybackrevive',
    'domain'        => 'app.reviveguard.com',
    'primary_color' => '#1a1a2e',
    'settings'      => [],
]);
```

### `PlanSeeder.php` — creates all 3 plans with Stripe Price IDs from `.env`:
```php
$plans = [
    [
        'name'                    => 'Monitor',
        'slug'                    => 'monitor',
        'price_monthly'           => 19.00,
        'stripe_price_id_monthly' => env('PLAN_MONITOR_PRICE_ID'),
        'features'                => [
            'uptime_monitoring'       => true,
            'ssl_monitoring'          => true,
            'domain_monitoring'       => true,
            'backup_frequency'        => 'monthly',
            'backup_retention_days'   => 30,
            'wp_core_updates'         => false,
            'wp_plugin_updates'       => false,
            'malware_scanning'        => false,
            'broken_link_check'       => false,
            'content_edits_hours'     => 0,
            'support_tickets_per_month' => 0,
            'report_frequency'        => 'monthly',
            'emergency_restore_sla_hours' => null,
            'priority_support'        => false,
        ],
    ],
    // ... Guard and Shield similarly
];
```

---

## Model Relationships Summary

```
Tenant hasMany Users
Tenant hasMany Clients
Tenant hasMany Plans
Tenant hasMany Sites (through Clients)

Client belongsTo Tenant
Client hasMany Sites
Client hasMany Subscriptions
Client hasMany Tickets

Site belongsTo Client
Site belongsTo Plan
Site hasMany SiteCommands
Site hasMany PluginSnapshots
Site hasMany Events
Site hasMany Backups
Site hasMany Reports
```

---

## Phase 1A Definition of Done

```
[ ] All migrations run without errors: php artisan migrate:fresh --seed
[ ] Tenants table has WaybackRevive record
[ ] Plans table has all 3 plans seeded
[ ] Admin user created: php artisan make:filament-user
[ ] Admin login works at app.reviveguard.com/admin
[ ] Portal login page accessible at portal.reviveguard.com/login
[ ] Horizon dashboard accessible at app.reviveguard.com/admin/horizon (auth-protected)
[ ] php artisan route:list shows agent/webhook/portal/admin routes
[ ] No n+1 queries on any seeded data (check telescope or debug bar)
[ ] All enum values match migration column definitions exactly
```
