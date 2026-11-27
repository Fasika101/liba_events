<?php

namespace App\Support;

use App\Models\Company;

class CompanySmsConfig
{
    /**
     * Prefer the organization's own key; otherwise use the platform Super Admin key.
     */
    public static function apiKey(Company $company): ?string
    {
        if (filled($company->sms_api_key)) {
            return (string) $company->sms_api_key;
        }

        return SmsConfig::apiKey();
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
        return filled(self::apiKey($company));
    }

    public static function canSend(Company $company): bool
    {
        if (! $company->isPremium()) {
            return false;
        }

        if (! self::hasApiKey($company)) {
            return false;
        }

        // Platform driver must be ready for live sending (log driver is fine for local).
        return SmsConfig::isConfigured();
    }
}
