<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            if ($row === null || $row->value === null || $row->value === '') {
                return $default;
            }

            return static::decryptIfNeeded($key, $row->value);
        });
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
    }

    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    private static function encryptIfNeeded(string $key, string $value): string
    {
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            return Crypt::encryptString($value);
        }

        return $value;
    }

    private static function decryptIfNeeded(string $key, string $value): string
    {
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            return Crypt::decryptString($value);
        }

        return $value;
    }
}
