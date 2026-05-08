<?php

namespace App\Filament\Pages;

use App\Models\PlatformSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Platform Settings admin page.
 *
 * All values are stored in the `platform_settings` table and cached for
 * 10 minutes. Changes take effect immediately — no server restart needed.
 *
 * Sensitive fields (API keys, secrets) are stored encrypted.
 */
class PlatformSettingsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Platform Settings';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.platform-settings-page';

    public array $data = [];

    // ── Boot ─────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    // ── Form definition ───────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('settings_tabs')
                    ->tabs([
                        // ── Billing (Whop) ─────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Billing (Whop)')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Toggle::make('whop_sandbox')
                                    ->label('Sandbox / Test Mode')
                                    ->helperText('When ON, all Whop calls go to sandbox.whop.com and use sandbox credentials. Turn OFF on production.')
                                    ->reactive()
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Production Credentials')
                                    ->description('Used when Sandbox Mode is OFF.')
                                    ->schema([
                                        Forms\Components\TextInput::make('whop_api_key')
                                            ->label('API Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Leave blank to keep existing value')
                                            ->helperText('From Whop dashboard → Developer → API keys'),

                                        Forms\Components\TextInput::make('whop_webhook_secret')
                                            ->label('Webhook Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Leave blank to keep existing value'),

                                        Forms\Components\TextInput::make('whop_plan_monitor_id')
                                            ->label('Monitor Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_plan_guard_id')
                                            ->label('Guard Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_plan_shield_id')
                                            ->label('Shield Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_checkout_base')
                                            ->label('Checkout Base URL')
                                            ->url()
                                            ->default('https://whop.com/checkout')
                                            ->placeholder('https://whop.com/checkout'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Sandbox Credentials')
                                    ->description('Used when Sandbox Mode is ON.')
                                    ->schema([
                                        Forms\Components\TextInput::make('whop_sandbox_api_key')
                                            ->label('Sandbox API Key')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Leave blank to keep existing value'),

                                        Forms\Components\TextInput::make('whop_sandbox_webhook_secret')
                                            ->label('Sandbox Webhook Secret')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Leave blank to keep existing value'),

                                        Forms\Components\TextInput::make('whop_sandbox_plan_monitor_id')
                                            ->label('Sandbox Monitor Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_sandbox_plan_guard_id')
                                            ->label('Sandbox Guard Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_sandbox_plan_shield_id')
                                            ->label('Sandbox Shield Plan ID')
                                            ->placeholder('plan_xxxxxxx'),

                                        Forms\Components\TextInput::make('whop_sandbox_checkout_base')
                                            ->label('Sandbox Checkout Base URL')
                                            ->url()
                                            ->default('https://sandbox.whop.com/checkout')
                                            ->placeholder('https://sandbox.whop.com/checkout'),
                                    ])->columns(2),
                            ]),

                        // ── Email (Resend) ─────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Email (Resend)')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\TextInput::make('resend_api_key')
                                    ->label('Resend API Key')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value')
                                    ->helperText('From resend.com → API Keys'),

                                Forms\Components\TextInput::make('resend_from')
                                    ->label('From Email Address')
                                    ->email()
                                    ->placeholder('team@reviveguard.com'),

                                Forms\Components\TextInput::make('resend_from_name')
                                    ->label('From Name')
                                    ->placeholder('ReviveGuard'),
                            ])->columns(2),

                        // ── Domain Monitoring (WhoisXML) ───────────────────
                        Forms\Components\Tabs\Tab::make('Domain Monitoring')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\TextInput::make('whoisxml_api_key')
                                    ->label('WhoisXML API Key')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value')
                                    ->helperText('From whoisxmlapi.com → My Products → API key. Hard-refresh checks cost 5 credits.')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // ── Uptime Monitoring (Uptime Kuma) ────────────────
                        Forms\Components\Tabs\Tab::make('Uptime Monitoring')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\TextInput::make('uptime_kuma_url')
                                    ->label('Uptime Kuma Base URL')
                                    ->url()
                                    ->placeholder('https://status.yourdomain.com')
                                    ->helperText('The root URL of your Uptime Kuma instance (no trailing slash)'),

                                Forms\Components\TextInput::make('uptime_kuma_api_key')
                                    ->label('API Key')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value'),

                                Forms\Components\TextInput::make('uptime_kuma_webhook_secret')
                                    ->label('Webhook Secret')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value')
                                    ->helperText('Must match the secret configured in Uptime Kuma → Notifications → Webhook'),
                            ])->columns(2),

                        // ── File Storage (Backblaze B2) ─────────────────────
                        Forms\Components\Tabs\Tab::make('File Storage (B2)')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Forms\Components\TextInput::make('b2_key_id')
                                    ->label('Key ID')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value'),

                                Forms\Components\TextInput::make('b2_app_key')
                                    ->label('Application Key')
                                    ->password()
                                    ->revealable()
                                    ->placeholder('Leave blank to keep existing value'),

                                Forms\Components\TextInput::make('b2_bucket_id')
                                    ->label('Bucket ID')
                                    ->placeholder('e.g. abc123bucket456'),

                                Forms\Components\TextInput::make('b2_bucket_name')
                                    ->label('Bucket Name')
                                    ->placeholder('e.g. reviveguard-backups'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    // ── Save action ───────────────────────────────────────────────────────────

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // ── Whop ──────────────────────────────────────────────────────────────
        PlatformSetting::setBool('whop_sandbox', (bool) ($data['whop_sandbox'] ?? false));

        $this->saveIfFilled('whop_api_key',                   $data['whop_api_key'] ?? null,                   encrypted: true);
        $this->saveIfFilled('whop_webhook_secret',            $data['whop_webhook_secret'] ?? null,            encrypted: true);
        $this->saveIfFilled('whop_sandbox_api_key',           $data['whop_sandbox_api_key'] ?? null,           encrypted: true);
        $this->saveIfFilled('whop_sandbox_webhook_secret',    $data['whop_sandbox_webhook_secret'] ?? null,    encrypted: true);

        PlatformSetting::set('whop_plan_monitor_id',          $data['whop_plan_monitor_id'] ?? null);
        PlatformSetting::set('whop_plan_guard_id',            $data['whop_plan_guard_id'] ?? null);
        PlatformSetting::set('whop_plan_shield_id',           $data['whop_plan_shield_id'] ?? null);
        PlatformSetting::set('whop_checkout_base',            $data['whop_checkout_base'] ?? null);
        PlatformSetting::set('whop_sandbox_plan_monitor_id',  $data['whop_sandbox_plan_monitor_id'] ?? null);
        PlatformSetting::set('whop_sandbox_plan_guard_id',    $data['whop_sandbox_plan_guard_id'] ?? null);
        PlatformSetting::set('whop_sandbox_plan_shield_id',   $data['whop_sandbox_plan_shield_id'] ?? null);
        PlatformSetting::set('whop_sandbox_checkout_base',    $data['whop_sandbox_checkout_base'] ?? null);

        // ── Email ─────────────────────────────────────────────────────────────
        $this->saveIfFilled('resend_api_key', $data['resend_api_key'] ?? null, encrypted: true);
        PlatformSetting::set('resend_from',      $data['resend_from'] ?? null);
        PlatformSetting::set('resend_from_name', $data['resend_from_name'] ?? null);

        // ── WhoisXML ──────────────────────────────────────────────────────────
        $this->saveIfFilled('whoisxml_api_key', $data['whoisxml_api_key'] ?? null, encrypted: true);

        // ── Uptime Kuma ───────────────────────────────────────────────────────
        PlatformSetting::set('uptime_kuma_url', $data['uptime_kuma_url'] ?? null);
        $this->saveIfFilled('uptime_kuma_api_key',        $data['uptime_kuma_api_key'] ?? null,        encrypted: true);
        $this->saveIfFilled('uptime_kuma_webhook_secret', $data['uptime_kuma_webhook_secret'] ?? null, encrypted: true);

        // ── Backblaze B2 ──────────────────────────────────────────────────────
        $this->saveIfFilled('b2_key_id',  $data['b2_key_id'] ?? null,  encrypted: true);
        $this->saveIfFilled('b2_app_key', $data['b2_app_key'] ?? null, encrypted: true);
        PlatformSetting::set('b2_bucket_id',   $data['b2_bucket_id'] ?? null);
        PlatformSetting::set('b2_bucket_name', $data['b2_bucket_name'] ?? null);

        Notification::make()
            ->title('Settings saved')
            ->body('All platform settings have been updated. Changes take effect immediately.')
            ->success()
            ->send();

        // Reload form so password fields show placeholder again (not decrypted values)
        $this->form->fill($this->loadSettings());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Only persist an encrypted field if the admin actually typed something.
     * Leaving the field blank keeps the existing DB value.
     */
    private function saveIfFilled(string $key, ?string $value, bool $encrypted): void
    {
        if ($value !== null && $value !== '') {
            PlatformSetting::set($key, $value, encrypted: $encrypted);
        }
    }

    /**
     * Load all current settings for the form.
     * Encrypted fields are intentionally left blank (never pre-fill passwords).
     */
    private function loadSettings(): array
    {
        $sandbox = PlatformSetting::getBool('whop_sandbox', config('services.whop.sandbox', false));

        return [
            // Whop
            'whop_sandbox'                 => $sandbox,
            'whop_api_key'                 => '',   // never pre-fill secrets
            'whop_webhook_secret'          => '',
            'whop_sandbox_api_key'         => '',
            'whop_sandbox_webhook_secret'  => '',
            'whop_plan_monitor_id'         => PlatformSetting::get('whop_plan_monitor_id',         config('services.whop.plan_monitor_id')),
            'whop_plan_guard_id'           => PlatformSetting::get('whop_plan_guard_id',           config('services.whop.plan_guard_id')),
            'whop_plan_shield_id'          => PlatformSetting::get('whop_plan_shield_id',          config('services.whop.plan_shield_id')),
            'whop_checkout_base'           => PlatformSetting::get('whop_checkout_base',           config('services.whop.checkout_base', 'https://whop.com/checkout')),
            'whop_sandbox_plan_monitor_id' => PlatformSetting::get('whop_sandbox_plan_monitor_id', config('services.whop.sandbox_plan_monitor_id')),
            'whop_sandbox_plan_guard_id'   => PlatformSetting::get('whop_sandbox_plan_guard_id',   config('services.whop.sandbox_plan_guard_id')),
            'whop_sandbox_plan_shield_id'  => PlatformSetting::get('whop_sandbox_plan_shield_id',  config('services.whop.sandbox_plan_shield_id')),
            'whop_sandbox_checkout_base'   => PlatformSetting::get('whop_sandbox_checkout_base',   'https://sandbox.whop.com/checkout'),

            // Email
            'resend_api_key'   => '',
            'resend_from'      => PlatformSetting::get('resend_from',      config('services.resend.from', 'team@reviveguard.com')),
            'resend_from_name' => PlatformSetting::get('resend_from_name', config('services.resend.from_name', 'ReviveGuard')),

            // WhoisXML
            'whoisxml_api_key' => '',

            // Uptime Kuma
            'uptime_kuma_url'            => PlatformSetting::get('uptime_kuma_url',            config('services.uptime_kuma.url', '')),
            'uptime_kuma_api_key'        => '',
            'uptime_kuma_webhook_secret' => '',

            // Backblaze B2
            'b2_key_id'      => '',
            'b2_app_key'     => '',
            'b2_bucket_id'   => PlatformSetting::get('b2_bucket_id',   config('services.backblaze.bucket_id', '')),
            'b2_bucket_name' => PlatformSetting::get('b2_bucket_name', config('services.backblaze.bucket_name', '')),
        ];
    }
}
