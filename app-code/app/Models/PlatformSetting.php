<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Platform-wide settings stored in the database.
 *
 * Uses a simple key/value table with optional encryption for secrets.
 * All reads are cached for 10 minutes and invalidated on write.
 *
 * Usage:
 *   PlatformSetting::get('whoisxml_api_key', 'fallback')
 *   PlatformSetting::getBool('whop_sandbox', false)
 *   PlatformSetting::set('whoisxml_api_key', 'abc123', encrypted: true)
 *   PlatformSetting::setBool('whop_sandbox', true)
 */
class PlatformSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Read a setting value, falling back to $default if not set.
     */
    public static function get(string $key, mixed $default = null): ?string
    {
        $record = Cache::remember("pset:{$key}", 600, fn () => static::find($key));

        if (! $record) {
            return $default !== null ? (string) $default : null;
        }

        try {
            return $record->is_encrypted ? decrypt($record->value) : (string) $record->value;
        } catch (\Exception) {
            return $default !== null ? (string) $default : null;
        }
    }

    /**
     * Read a setting as a boolean (stores '1'/'0').
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::get($key);
        if ($val === null) {
            return $default;
        }
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Write a setting value. Pass encrypted: true for API keys / secrets.
     */
    public static function set(string $key, ?string $value, bool $encrypted = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value'        => ($encrypted && $value !== null && $value !== '') ? encrypt($value) : $value,
                'is_encrypted' => $encrypted,
            ]
        );

        Cache::forget("pset:{$key}");
    }

    /**
     * Write a boolean setting.
     */
    public static function setBool(string $key, bool $value): void
    {
        static::set($key, $value ? '1' : '0', encrypted: false);
    }

    /**
     * Forget the cached value for a key (useful after config:cache).
     */
    public static function invalidate(string $key): void
    {
        Cache::forget("pset:{$key}");
    }
}
