<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VerifyEtClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.verify_et.api_key'));
    }

    /**
     * @return array{ok: bool, request_id: ?string, status: ?string, raw: array}
     */
    public function verify(string $bank, string $reference, ?string $settlementAccount = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Verify.et is not configured.');
        }

        $payload = [
            'bank' => $bank,
            'reference' => $reference,
        ];

        if (filled($settlementAccount)) {
            $payload['settlementAccount'] = $settlementAccount;
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.verify_et.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Idempotency-Key' => 'order-'.md5($bank.'|'.$reference),
        ])
            ->timeout(20)
            ->post(rtrim((string) config('services.verify_et.base_url'), '/').'/api/verify', $payload);

        $json = $response->json() ?? [];

        if ($response->failed() && $response->status() !== 202) {
            Log::warning('Verify.et request failed', [
                'status' => $response->status(),
                'body' => $json,
            ]);
        }

        $verification = $json['data']['verification'] ?? $json['verification'] ?? $json['data'] ?? [];

        return [
            'ok' => $response->successful() || $response->status() === 202,
            'request_id' => $json['requestId'] ?? $json['data']['requestId'] ?? ($verification['id'] ?? null),
            'status' => $verification['status'] ?? $json['status'] ?? ($response->status() === 202 ? 'pending' : null),
            'raw' => $json,
        ];
    }
}
