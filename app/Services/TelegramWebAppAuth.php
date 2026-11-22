<?php

namespace App\Services;

use App\Models\Attendee;
use Illuminate\Support\Facades\Log;

class TelegramWebAppAuth
{
    public function validateInitData(?string $initData): ?array
    {
        if (! filled($initData)) {
            return null;
        }

        $token = config('services.telegram.bot_token');
        if (! filled($token)) {
            return null;
        }

        parse_str($initData, $data);
        if (! isset($data['hash'])) {
            return null;
        }

        $checkHash = $data['hash'];
        unset($data['hash']);
        ksort($data);

        $lines = [];
        foreach ($data as $key => $value) {
            $lines[] = $key.'='.$value;
        }
        $dataCheckString = implode("\n", $lines);

        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculated, $checkHash)) {
            Log::info('Telegram initData hash mismatch');

            return null;
        }

        if (isset($data['auth_date']) && (time() - (int) $data['auth_date']) > 86400) {
            return null;
        }

        $user = isset($data['user']) ? json_decode($data['user'], true) : null;
        if (! is_array($user) || empty($user['id'])) {
            return null;
        }

        return $user;
    }

    public function upsertAttendeeFromTelegramUser(array $user, string $locale = 'en'): Attendee
    {
        $telegramId = (string) $user['id'];
        $first = $user['first_name'] ?? null;
        $last = $user['last_name'] ?? null;
        $username = $user['username'] ?? null;
        $name = trim(($first ?? '').' '.($last ?? '')) ?: ($username ?: 'Telegram user');

        return Attendee::query()->updateOrCreate(
            ['telegram_id' => $telegramId],
            [
                'telegram_username' => $username,
                'first_name' => $first,
                'last_name' => $last,
                'name' => $name,
                'locale' => in_array($locale, ['en', 'am'], true) ? $locale : 'en',
            ]
        );
    }
}
