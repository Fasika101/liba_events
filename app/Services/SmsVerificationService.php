<?php

namespace App\Services;

use App\Services\Sms\SmsethiopiaClient;
use App\Support\SmsConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SmsVerificationService
{
    public function __construct(
        private readonly SmsethiopiaClient $smsethiopia
    ) {}

    public function sendOtp(string $phone): string
    {
        $code = $this->generateCode();
        $ttl = SmsConfig::otpExpiryMinutes();

        Cache::put($this->cacheKey($phone), $code, now()->addMinutes($ttl));

        $message = SmsConfig::buildMessage($code);
        $driver = SmsConfig::driver();

        if ($driver === 'smsethiopia') {
            $this->smsethiopia->send($phone, $message);
        } elseif ($driver === 'log') {
            Log::info('SMS OTP sent', ['phone' => $phone, 'code' => $code, 'message' => $message]);
        } else {
            throw new RuntimeException("Unsupported SMS driver: {$driver}");
        }

        return $code;
    }

    public function sendTestMessage(string $phone, ?string $text = null): void
    {
        $this->sendMessage($phone, $text ?? SmsConfig::buildMessage('123456'));
    }

    public function sendMessage(string $phone, string $message, ?string $apiKey = null): void
    {
        $driver = SmsConfig::driver();

        if ($driver === 'smsethiopia') {
            $this->smsethiopia->send($phone, $message, $apiKey);
        } elseif ($driver === 'log') {
            Log::info('SMS message sent', ['phone' => $phone, 'message' => $message, 'company_api' => filled($apiKey)]);
        } else {
            throw new RuntimeException("Unsupported SMS driver: {$driver}");
        }
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $stored = Cache::get($this->cacheKey($phone));

        if ($stored === null || ! hash_equals((string) $stored, trim($code))) {
            return false;
        }

        Cache::forget($this->cacheKey($phone));

        return true;
    }

    public function hasPendingOtp(string $phone): bool
    {
        return Cache::has($this->cacheKey($phone));
    }

    private function generateCode(): string
    {
        $length = SmsConfig::otpLength();

        return Str::padLeft((string) random_int(0, 10 ** $length - 1), $length, '0');
    }

    private function cacheKey(string $phone): string
    {
        return 'sms_otp:'.md5($phone);
    }
}
