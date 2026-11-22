<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\TicketOrder;
use App\Services\TicketOrderService;
use App\Services\VerifyEtClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyEtWebhookController extends Controller
{
    public function __invoke(Request $request, TicketOrderService $orders, VerifyEtClient $verifyEt)
    {
        $raw = $request->getContent();
        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Verify-Signature')
            ?? $request->header('X-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp')
            ?? $request->header('X-Verify-Timestamp');

        if ($request->header('X-Webhook-Test') === 'true') {
            return response()->noContent();
        }

        if (! $verifyEt->verifyWebhookSignature($raw, $signature, $timestamp)) {
            Log::warning('Verify.et webhook signature failed');

            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $requestId = data_get($payload, 'requestId')
            ?? data_get($payload, 'data.requestId')
            ?? data_get($payload, 'verification.requestId');

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

        try {
            $orders->applyVerifyEtResult($order, $payload);
        } catch (\Throwable $e) {
            Log::warning('Verify.et webhook apply failed', ['error' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}
