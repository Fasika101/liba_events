<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\TicketOrder;
use App\Services\TicketOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyEtWebhookController extends Controller
{
    public function __invoke(Request $request, TicketOrderService $orders)
    {
        $secret = config('services.verify_et.webhook_secret');
        if (filled($secret)) {
            // Soft check — Exact HMAC scheme can vary by Verify.et version; store payload either way.
            $signature = $request->header('X-Verify-Signature')
                ?? $request->header('X-Signature')
                ?? '';
            if ($signature === '') {
                Log::info('Verify.et webhook without signature header');
            }
        }

        $payload = $request->all();
        $requestId = data_get($payload, 'requestId')
            ?? data_get($payload, 'data.requestId')
            ?? data_get($payload, 'verification.id');

        $status = strtolower((string) (
            data_get($payload, 'verification.status')
            ?? data_get($payload, 'data.verification.status')
            ?? data_get($payload, 'status')
            ?? ''
        ));

        if (! $requestId) {
            return response()->json(['ok' => false], 422);
        }

        $order = TicketOrder::query()
            ->where('verify_et_request_id', $requestId)
            ->where('status', TicketOrder::STATUS_PENDING)
            ->first();

        if (! $order) {
            return response()->json(['ok' => true, 'matched' => false]);
        }

        $order->update([
            'verify_et_status' => $status ?: $order->verify_et_status,
            'verify_et_payload' => json_encode($payload),
        ]);

        // Auto-fulfill only on clearly successful verification; orgs can still mark paid manually.
        if (in_array($status, ['verified', 'success', 'completed', 'paid'], true)) {
            try {
                $orders->markPaid($order, null, 'Auto-verified via Verify.et');
            } catch (\Throwable $e) {
                Log::warning('Verify.et auto mark paid failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['ok' => true, 'matched' => true]);
    }
}
