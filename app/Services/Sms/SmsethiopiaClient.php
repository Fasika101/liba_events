<?php

namespace App\Services\Sms;

use App\Support\SmsConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsethiopiaClient
{
    private const SEND_URL = 'https://smsethiopia.com/api/sms/send';

    public function send(string $phone, string $text, ?string $apiKey = null): array
    {
        $apiKey = $apiKey ?? SmsConfig::apiKey();

        if (! filled($apiKey)) {
            throw new RuntimeException('SMSEthiopia API key is not configured.');
        }

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post(self::SEND_URL, [
                'msisdn' => $this->toMsisdn($phone),
                'text' => $text,
            ]);

        $payload = $response->json() ?? [];

        if (! $response->successful() || ! ($payload['sent'] ?? false)) {
            Log::error('SMSEthiopia SMS failed', [
                'status' => $response->status(),
                'body' => $payload,
                'msisdn' => $this->toMsisdn($phone),
            ]);

            throw new RuntimeException(
                $payload['description'] ?? $payload['message'] ?? 'SMSEthiopia rejected the SMS request.'
            );
        }

        return $payload;
    }

    public function toMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '251')) {
            return substr($digits, 0, 12);
        }

        return '251'.substr(ltrim($digits, '0'), 0, 9);
    }
}
