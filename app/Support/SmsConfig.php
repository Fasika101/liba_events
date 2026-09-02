<?php

namespace App\Support;

use App\Models\Setting;

class SmsConfig
{
    public static function driver(): string
    {
        return (string) Setting::get('sms.driver', config('events.sms.driver', 'log'));
    }

    public static function apiKey(): ?string
    {
        $key = Setting::get('sms.api_key', env('SMS_API_KEY'));

        return filled($key) ? (string) $key : null;
    }

    public static function senderId(): ?string
    {
        $senderId = Setting::get('sms.sender_id', env('SMS_SENDER_ID'));

        return filled($senderId) ? (string) $senderId : null;
    }

    public static function messageTemplate(): string
    {
        return (string) Setting::get(
            'sms.message_template',
            'Your {app_name} verification code is: {code}'
        );
    }

    public static function otpLength(): int
    {
        return (int) Setting::get('sms.otp_length', config('events.sms.otp_length', 6));
    }

    public static function otpExpiryMinutes(): int
    {
        return (int) Setting::get('sms.otp_expiry_minutes', config('events.sms.otp_expiry_minutes', 10));
    }

    public static function buildMessage(string $code): string
    {
        return str_replace(
            ['{app_name}', '{code}', '{sender_id}'],
            [config('app.name'), $code, self::senderId() ?? ''],
            self::messageTemplate()
        );
    }

    public static function isConfigured(): bool
    {
        if (self::driver() === 'log') {
            return true;
        }

        return self::driver() === 'smsethiopia' && filled(self::apiKey());
    }

    public static function senderIdPriceEtb(): ?float
    {
        $price = Setting::get('sms.sender_id_price_etb');

        return filled($price) ? (float) $price : null;
    }
}
