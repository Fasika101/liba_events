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
     * Submit a verification per Verify.et docs, then poll briefly so checkout
     * can confirm payment in the same request when the bank finishes quickly.
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
     *   previously_confirmed: bool,
     *   receiver_account: ?string,
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

        $headers = [
            'x-api-key' => config('services.verify_et.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Idempotency-Key' => $idempotencyKey ?: ('order-'.md5($bank.'|'.$reference.'|'.($expectedAmount ?? ''))),
        ];

        // One-off callback so live still completes if the dashboard webhook is missing.
        $webhookUrl = config('services.verify_et.webhook_url') ?: url('/webhooks/verify-et');
        if (filled($webhookUrl)) {
            $headers['X-Webhook-Url'] = $webhookUrl;
            $payload['webhookUrl'] = $webhookUrl;
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->post(
                rtrim((string) config('services.verify_et.base_url'), '/').'/api/verify?waitMs='.$waitMs,
                $payload
            );

        $json = $response->json() ?? [];

        if ($response->failed() && $response->status() !== 202) {
            Log::warning('Verify.et request failed', [
                'status' => $response->status(),
                'body' => $json,
                'bank' => $bank,
            ]);
        }

        $result = $this->interpretResult(
            $json,
            $expectedAmount,
            $settlementAccount,
            $response->successful() || $response->status() === 202
        );

        // 202 / pending: poll status URL so the buyer gets an immediate answer when possible.
        if (
            $result['request_id']
            && ! $result['verified']
            && in_array($result['status'] ?? '', ['pending', 'queued', null, ''], true)
        ) {
            $result = $this->pollUntilDone(
                $result['request_id'],
                $expectedAmount,
                $settlementAccount,
                $result,
                $bank === 'telebirr' ? 5 : 4
            );
        }

        return $result;
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
     *   previously_confirmed: bool,
     *   receiver_account: ?string,
     *   raw: array
     * }
     */
    public function fetchStatus(
        string $requestId,
        ?float $expectedAmount = null,
        ?string $settlementAccount = null,
    ): array {
        $response = Http::withHeaders([
            'x-api-key' => config('services.verify_et.api_key'),
            'Accept' => 'application/json',
        ])
            ->timeout(20)
            ->get(rtrim((string) config('services.verify_et.base_url'), '/').'/api/verify/'.$requestId);

        $json = $response->json() ?? [];

        return $this->interpretResult(
            $json,
            $expectedAmount,
            $settlementAccount,
            $response->successful()
        );
    }

    /**
     * @param  array<string, mixed>  $seed
     * @return array<string, mixed>
     */
    private function pollUntilDone(
        string $requestId,
        ?float $expectedAmount,
        ?string $settlementAccount,
        array $seed,
        int $attempts = 4,
    ): array {
        $result = $seed;

        for ($i = 0; $i < $attempts; $i++) {
            usleep(1_500_000);

            try {
                $result = $this->fetchStatus($requestId, $expectedAmount, $settlementAccount);
            } catch (\Throwable $e) {
                Log::warning('Verify.et poll failed', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
                break;
            }

            if ($result['verified'] || ! in_array($result['status'] ?? '', ['pending', 'queued', null, ''], true)) {
                break;
            }
        }

        return $result;
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
     *   previously_confirmed: bool,
     *   receiver_account: ?string,
     *   raw: array
     * }
     */
    public function interpretResult(
        array $payload,
        ?float $expectedAmount = null,
        ?string $expectedSettlementAccount = null,
        bool $ok = true,
    ): array {
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

        $receiverAccount = data_get($item, 'receiverAccount')
            ?? data_get($item, 'settlementAccountMatch.receiverAccount')
            ?? data_get($verification, 'settlementAccountMatch.receiverAccount');

        // Local fallback when Verify.et returns a masked receiver but no match object.
        if ($settlement === null && filled($expectedSettlementAccount) && filled($receiverAccount)) {
            $settlement = $this->accountsLikelyMatch((string) $expectedSettlementAccount, (string) $receiverAccount);
        }

        $amountMatched = null;
        if ($expectedAmount !== null && $amount !== null) {
            $amountMatched = $this->amountsMatch($expectedAmount, $amount);
        }

        $confirmedBefore = (bool) (
            data_get($item, 'confirmationHistory.confirmedBefore')
            ?? data_get($item, 'confirmationHistory.isFirstConfirmation') === false
        );

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
            'previously_confirmed' => $confirmedBefore,
            'receiver_account' => filled($receiverAccount) ? (string) $receiverAccount : null,
            'raw' => $payload,
        ];
    }

    public function amountsMatch(float $expected, float $actual, float $tolerance = 0.01): bool
    {
        return abs($expected - $actual) <= $tolerance;
    }

    /**
     * Compare org account vs masked/full receiver account from Verify.et.
     */
    public function accountsLikelyMatch(string $expected, string $received): bool
    {
        $expectedDigits = preg_replace('/\D+/', '', $expected) ?? '';
        $receivedDigits = preg_replace('/\D+/', '', $received) ?? '';
        $receivedVisible = preg_replace('/[^0-9]/', '', $received) ?? '';

        if ($expectedDigits === '' || ($receivedDigits === '' && $receivedVisible === '')) {
            return false;
        }

        if ($receivedDigits !== '' && ($expectedDigits === $receivedDigits || str_ends_with($expectedDigits, $receivedDigits) || str_ends_with($receivedDigits, $expectedDigits))) {
            return true;
        }

        // Masked patterns like 1****7441 or 2519****0897 — match visible suffix/prefix digits.
        if (preg_match('/^(\d+)\*+(\d+)$/', preg_replace('/\s+/', '', $received) ?? '', $m)) {
            $prefix = $m[1];
            $suffix = $m[2];

            return str_starts_with($expectedDigits, $prefix) && str_ends_with($expectedDigits, $suffix);
        }

        if (strlen($receivedVisible) >= 4 && str_ends_with($expectedDigits, $receivedVisible)) {
            return true;
        }

        return false;
    }

    public static function normalizeReference(string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return '';
        }

        if (preg_match('#https?://#i', $reference)) {
            return $reference;
        }

        // Prefer a clear FT… bank reference when pasted inside SMS text.
        if (preg_match('/\b(FT[0-9A-Z]+)\b/i', $reference, $m)) {
            return strtoupper($m[1]);
        }

        return strtoupper((string) preg_replace('/\s+/', '', $reference));
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
        $normalizedRef = self::normalizeReference($reference);
        $refForApi = $normalizedRef !== '' ? $normalizedRef : $reference;

        switch ($bank) {
            case 'telebirr':
            case 'mpesa':
                $payload['transactionNumber'] = $refForApi;
                $payload['reference'] = $refForApi;
                break;
            case 'cbebirr':
                $payload['receiptNumber'] = $refForApi;
                $payload['reference'] = $refForApi;
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
                $payload['referenceNumber'] = $refForApi;
                $payload['reference'] = $refForApi;
                break;
            case 'cbe':
            default:
                $payload['reference'] = $refForApi;
                $payload['referenceNumber'] = $refForApi;
                // Older CBE FT… references require the receiver account (org settlement account).
                if (filled($settlementAccount) && $this->looksLikeCbeFtReference($refForApi)) {
                    $digits = preg_replace('/\D+/', '', $settlementAccount) ?? '';
                    if (strlen($digits) >= 8) {
                        $payload['accountNumber'] = $digits;
                        $payload['accountSuffix'] = substr($digits, -8);
                        $payload['suffix'] = substr($digits, -8);
                    }
                }
                break;
        }

        if (filled($settlementAccount)) {
            $payload['settlementAccount'] = preg_replace('/\s+/', '', $settlementAccount) ?: $settlementAccount;
        }

        return $payload;
    }

    private function looksLikeCbeFtReference(string $reference): bool
    {
        return (bool) preg_match('/^FT[0-9A-Z]+$/i', $reference)
            || (bool) preg_match('/\bFT[0-9A-Z]{6,}\b/i', $reference);
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
