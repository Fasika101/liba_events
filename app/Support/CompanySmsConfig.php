<?php

namespace App\Support;

use App\Models\Company;

class CompanySmsConfig
{
    public static function apiKey(Company $company): ?string
    {
        return filled($company->sms_api_key) ? (string) $company->sms_api_key : null;
    }

    public static function senderId(Company $company): ?string
    {
        if (filled($company->sms_sender_id)) {
            return (string) $company->sms_sender_id;
        }

        return SmsConfig::senderId();
    }

    public static function hasApiKey(Company $company): bool
    {
        return filled($company->sms_api_key);
    }

    public static function canSend(Company $company): bool
    {
        if (! $company->isPremium()) {
            return false;
        }

        if (SmsConfig::driver() === 'log') {
            return self::hasApiKey($company);
        }

        return self::hasApiKey($company) && SmsConfig::isConfigured();
    }
}
