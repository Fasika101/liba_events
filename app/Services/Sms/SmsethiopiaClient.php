<?php

namespace App\Services\Sms;

use App\Support\SmsConfig;
use Illuminate\Http\Client\ConnectionException;
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
            throw new RuntimeException('SMSEthiopia API key is not configured (or cannot be decrypted — re-paste it in Super Admin → SMS settings).');
        }

        $msisdn = $this->toMsisdn($phone);

        try {
            $request = Http::timeout(25)
                ->acceptJson()
                ->withHeaders([
                    'KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                ]);

            // Some cPanel hosts ship without a working CA bundle.
            if (! filter_var(config('events.sms.verify_ssl', true), FILTER_VALIDATE_BOOLEAN)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->post(self::SEND_URL, [
                'msisdn' => $msisdn,
                'text' => $text,
            ]);
        } catch (ConnectionException $e) {
            Log::error('SMSEthiopia connection failed', [
                'msisdn' => $msisdn,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'Cannot reach smsethiopia.com from this server ('.$e->getMessage().'). '
                .'Ask your host to allow outbound HTTPS to smsethiopia.com, or set SMS_VERIFY_SSL=false if the error is SSL/certificate related.'
            );
        } catch (\Throwable $e) {
            Log::error('SMSEthiopia request exception', [
                'msisdn' => $msisdn,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('SMS request failed: '.$e->getMessage());
        }

        $payload = $response->json() ?? [];
        $bodyPreview = is_array($payload) ? $payload : ['raw' => $response->body()];

        if (! $response->successful() || ! ($payload['sent'] ?? false)) {
            Log::error('SMSEthiopia SMS failed', [
                'status' => $response->status(),
                'body' => $bodyPreview,
                'msisdn' => $msisdn,
            ]);

            $detail = $payload['description']
                ?? $payload['message']
                ?? trim((string) $response->body())
                ?: 'HTTP '.$response->status();

            throw new RuntimeException('SMSEthiopia rejected the SMS: '.$detail);
        }

        return $payload;
    }

    /**
     * Lightweight connectivity check used by Super Admin diagnostics.
     *
     * @return array{ok: bool, message: string, http_status: ?int}
     */
    public function probe(): array
    {
        try {
            $request = Http::timeout(12)->acceptJson();
            if (! filter_var(config('events.sms.verify_ssl', true), FILTER_VALIDATE_BOOLEAN)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->get('https://smsethiopia.com/');

            return [
                'ok' => $response->successful() || $response->status() < 500,
                'message' => 'Reached smsethiopia.com (HTTP '.$response->status().')',
                'http_status' => $response->status(),
            ];
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'message' => 'Outbound connection failed: '.$e->getMessage(),
                'http_status' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Probe failed: '.$e->getMessage(),
                'http_status' => null,
            ];
        }
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
