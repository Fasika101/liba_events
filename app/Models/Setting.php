<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    /** @var list<string> */
    private const ENCRYPTED_KEYS = [
        'sms.api_key',
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        // Never forever-cache a "missing" row as null — that would hide a later .env fallback
        // and made live SMS keys look "forgotten" after deploys/cache clears.
        if (Cache::has("setting.{$key}")) {
            $cached = Cache::get("setting.{$key}");

            return $cached === null ? $default : $cached;
        }

        $row = static::query()->where('key', $key)->first();

        if ($row === null || $row->value === null || $row->value === '') {
            return $default;
        }

        $value = static::decryptIfNeeded($key, $row->value);
        if ($value === null) {
            return $default;
        }

        Cache::forever("setting.{$key}", $value);

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            static::query()->where('key', $key)->delete();
            Cache::forget("setting.{$key}");

            return;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => static::encryptIfNeeded($key, (string) $value)]
        );

        Cache::forget("setting.{$key}");
        // Warm cache immediately so subsequent requests never see a miss flicker.
        Cache::forever("setting.{$key}", (string) $value);
    }

    public static function has(string $key): bool
    {
        return static::query()->where('key', $key)->whereNotNull('value')->where('value', '!=', '')->exists();
    }

    public static function masked(string $key): ?string
    {
        $value = static::get($key);
        if (! filled($value)) {
            return null;
        }

        $value = (string) $value;
        if (strlen($value) <= 4) {
            return str_repeat('•', strlen($value));
        }

        return str_repeat('•', max(8, strlen($value) - 4)).substr($value, -4);
    }

    private static function encryptIfNeeded(string $key, string $value): string
    {
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            return Crypt::encryptString($value);
        }

        return $value;
    }

    private static function decryptIfNeeded(string $key, string $value): ?string
    {
        if (! in_array($key, self::ENCRYPTED_KEYS, true)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Legacy plaintext rows, or APP_KEY changed — keep serving plaintext if it looks like one.
            if (! str_starts_with($value, 'eyJpdiI')) {
                return $value;
            }

            Log::warning('Failed to decrypt setting; APP_KEY may have changed.', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
