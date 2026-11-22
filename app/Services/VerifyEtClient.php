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
     * Submit a verification per Verify.et docs.
     * Verify.et returns the receipt amount; we compare it to $expectedAmount locally.
     *
     * @return array{
     *   ok: bool,
     *   request_id: ?string,
     *   status: ?string,
     *   verified: bool,
     *   amount: ?float,
     *   currency: ?string,
     *   settlement_matched: ?bool,
     *   amount_matched: ?bool,
     *   raw: array
     * }
     */
    public function verify(
        string $bank,
        string $reference,
        ?string $settlementAccount = null,
        ?float $expectedAmount = null,
        ?string $phone = null,
        ?string $idempotencyKey = null,
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Verify.et is not configured.');
        }

        $bank = strtolower(trim($bank));
        $reference = trim($reference);
        $payload = $this->buildPayload($bank, $reference, $settlementAccount, $phone);
        $waitMs = $bank === 'telebirr' ? 8000 : 5000;

        $response = Http::withHeaders([
            'x-api-key' => config('services.verify_et.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Idempotency-Key' => $idempotencyKey ?: ('order-'.md5($bank.'|'.$reference.'|'.($expectedAmount ?? ''))),
        ])
            ->timeout(25)
            ->post(
                rtrim((string) config('services.verify_et.base_url'), '/').'/api/verify?waitMs='.$waitMs,
                $payload
            );

        $json = $response->json() ?? [];

        if ($response->failed() && $response->status() !== 202) {
            Log::warning('Verify.et request failed', [
                'status' => $response->status(),
                'body' => $json,
            ]);
        }

        return $this->interpretResult(
            $json,
            $expectedAmount,
            $response->successful() || $response->status() === 202
        );
    }

    /**
     * @return array{
     *   ok: bool,
     *   request_id: ?string,
     *   status: ?string,
     *   verified: bool,
     *   amount: ?float,
     *   currency: ?string,
     *   settlement_matched: ?bool,
     *   amount_matched: ?bool,
     *   raw: array
     * }
     */
    public function interpretResult(array $payload, ?float $expectedAmount = null, bool $ok = true): array
    {
        $verification = data_get($payload, 'verification')
            ?? data_get($payload, 'data.verification')
            ?? [];

        $item = null;
        if (is_array(data_get($payload, 'data.0'))) {
            $item = data_get($payload, 'data.0');
        } elseif (is_array(data_get($payload, 'data')) && (
            array_key_exists('amount', (array) data_get($payload, 'data'))
            || array_key_exists('verified', (array) data_get($payload, 'data'))
            || array_key_exists('status', (array) data_get($payload, 'data'))
        )) {
            $item = data_get($payload, 'data');
        }

        $status = strtolower((string) (
            data_get($item, 'status')
            ?? data_get($verification, 'status')
            ?? data_get($payload, 'status')
            ?? ''
        ));

        $verifiedFlag = data_get($item, 'verified');
        if ($verifiedFlag === null) {
            $verifiedFlag = data_get($verification, 'verified');
        }

        $verified = $verifiedFlag === true
            || in_array($status, ['success', 'verified', 'completed', 'paid'], true);

        $amount = $this->parseAmount(
            data_get($item, 'amount')
            ?? data_get($verification, 'amount')
            ?? data_get($payload, 'amount')
        );

        $settlement = data_get($item, 'settlementAccountMatch.matched');
        if ($settlement === null) {
            $settlement = data_get($verification, 'settlementAccountMatch.matched');
        }

        $amountMatched = null;
        if ($expectedAmount !== null && $amount !== null) {
            $amountMatched = $this->amountsMatch($expectedAmount, $amount);
        }

        return [
            'ok' => $ok,
            'request_id' => data_get($payload, 'requestId')
                ?? data_get($payload, 'data.requestId')
                ?? data_get($verification, 'requestId'),
            'status' => $status !== '' ? $status : null,
            'verified' => $verified,
            'amount' => $amount,
            'currency' => data_get($item, 'currency') ?? data_get($verification, 'currency'),
            'settlement_matched' => is_bool($settlement) ? $settlement : null,
            'amount_matched' => $amountMatched,
            'raw' => $payload,
        ];
    }

    public function amountsMatch(float $expected, float $actual, float $tolerance = 0.01): bool
    {
        return abs($expected - $actual) <= $tolerance;
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader, ?string $timestampHeader): bool
    {
        $secret = config('services.verify_et.webhook_secret');
        if (! filled($secret)) {
            return true;
        }

        if (! filled($signatureHeader) || ! filled($timestampHeader)) {
            return false;
        }

        $signed = hash_hmac('sha256', $timestampHeader.'.'.$rawBody, $secret);
        foreach (explode(',', $signatureHeader) as $part) {
            $part = trim($part);
            $candidate = str_starts_with($part, 'sha256=') ? substr($part, 7) : $part;
            if (hash_equals($signed, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Full SMS text can be pasted into reference — Verify.et best-effort extracts fields/amount.
     */
    private function buildPayload(string $bank, string $reference, ?string $settlementAccount, ?string $phone): array
    {
        $payload = ['bank' => $bank];

        switch ($bank) {
            case 'telebirr':
            case 'mpesa':
                $payload['transactionNumber'] = $reference;
                $payload['reference'] = $reference;
                break;
            case 'cbebirr':
                $payload['receiptNumber'] = $reference;
                $payload['reference'] = $reference;
                if (filled($phone)) {
                    $payload['phone'] = $phone;
                    $payload['phoneNumber'] = $phone;
                }
                break;
            case 'boa':
            case 'dashen':
            case 'awash':
            case 'siinqee':
            case 'kaafiebirr':
            case 'coopayebirr':
                $payload['referenceNumber'] = $reference;
                $payload['reference'] = $reference;
                break;
            case 'cbe':
            default:
                $payload['reference'] = $reference;
                $payload['referenceNumber'] = $reference;
                break;
        }

        if (filled($settlementAccount)) {
            $payload['settlementAccount'] = $settlementAccount;
        }

        return $payload;
    }

    private function parseAmount(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        if (is_string($raw) && preg_match('/([0-9]+(?:\.[0-9]+)?)/', str_replace(',', '', $raw), $m)) {
            return round((float) $m[1], 2);
        }

        return null;
    }
}
