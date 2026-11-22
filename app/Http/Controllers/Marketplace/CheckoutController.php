<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Services\TicketOrderService;
use App\Services\VerifyEtClient;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function previewVerify(Request $request, Event $event, VerifyEtClient $verifyEt)
    {
        $event->load(['company.bankAccounts' => fn ($q) => $q->where('is_active', true)]);

        if (! $event->company || ! $event->company->isPremium() || $event->company->isSuspended() || $event->status !== 'active' || $event->isExpired()) {
            return response()->json(['ok' => false, 'state' => 'unavailable', 'message' => __('public.event_expired')], 422);
        }

        $data = $request->validate([
            'bank_code' => ['nullable', 'string', 'max:40'],
            'transaction_reference' => ['required', 'string', 'max:2000'],
            'buyer_phone' => ['nullable', 'string', 'max:20'],
        ]);

        if (! $verifyEt->isConfigured()) {
            return response()->json([
                'ok' => true,
                'state' => 'manual',
                'message' => __('public.verify_manual'),
            ]);
        }

        $bankCode = $data['bank_code'] ?? null;
        $accounts = $event->company->bankAccounts;
        $account = $accounts->first(function ($a) use ($bankCode) {
            if (! $bankCode) {
                return true;
            }

            return $a->bank_code === $bankCode || $a->bank_name === $bankCode;
        }) ?? $accounts->first();

        $bank = strtolower((string) ($account?->bank_code ?: $bankCode ?: 'cbe'));
        if (! in_array($bank, ['cbe', 'telebirr', 'cbebirr', 'boa', 'mpesa', 'dashen', 'awash', 'siinqee', 'kaafiebirr', 'coopayebirr'], true)) {
            $bank = 'cbe';
        }

        try {
            $result = $verifyEt->verify(
                $bank,
                $data['transaction_reference'],
                $account?->account_number,
                (float) $event->price,
                $data['buyer_phone'] ?? null,
                'preview-'.md5($event->id.'|'.$data['transaction_reference'])
            );
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'state' => 'error',
                'message' => __('public.verify_error'),
            ]);
        }

        if (($result['amount_matched'] ?? null) === true && ($result['verified'] ?? false) === true) {
            if (($result['settlement_matched'] ?? null) === false) {
                return response()->json([
                    'ok' => false,
                    'state' => 'settlement_mismatch',
                    'message' => __('public.verify_settlement_mismatch'),
                    'amount' => $result['amount'],
                ]);
            }

            return response()->json([
                'ok' => true,
                'state' => 'verified',
                'message' => __('public.verify_success', [
                    'amount' => number_format((float) $result['amount'], 2),
                    'currency' => $result['currency'] ?: $event->currency,
                ]),
                'amount' => $result['amount'],
                'request_id' => $result['request_id'],
            ]);
        }

        if (($result['amount_matched'] ?? null) === false) {
            return response()->json([
                'ok' => false,
                'state' => 'amount_mismatch',
                'message' => __('public.verify_amount_mismatch', [
                    'paid' => number_format((float) ($result['amount'] ?? 0), 2),
                    'expected' => number_format((float) $event->price, 2),
                    'currency' => $event->currency,
                ]),
                'amount' => $result['amount'],
            ]);
        }

        if (($result['verified'] ?? false) === false && in_array(($result['status'] ?? ''), ['pending', 'queued', ''], true)) {
            return response()->json([
                'ok' => true,
                'state' => 'pending',
                'message' => __('public.verify_pending'),
                'request_id' => $result['request_id'],
            ]);
        }

        return response()->json([
            'ok' => false,
            'state' => 'not_found',
            'message' => __('public.verify_not_found'),
            'request_id' => $result['request_id'] ?? null,
        ]);
    }

    public function store(Request $request, Event $event, TicketOrderService $orders)
    {
        $event->load('company');

        if ($event->isExpired()) {
            return back()->withErrors(['checkout' => __('public.event_expired')])->withInput();
        }

        // Normalize phone: UI sends 9 local digits; we store +251XXXXXXXXX
        $local = preg_replace('/\D+/', '', (string) $request->input('buyer_phone_local', ''));
        if (strlen($local) === 9) {
            $request->merge(['buyer_phone' => '+251'.$local]);
        } elseif (preg_match('/^\+251(\d{9})$/', (string) $request->input('buyer_phone'), $m)) {
            $request->merge(['buyer_phone' => '+251'.$m[1]]);
        }

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'buyer_address' => ['nullable', 'string', 'max:500'],
            'buyer_occupation' => ['nullable', 'string', 'max:150'],
            'yeneshaa_abat' => ['required', 'in:0,1'],
            'bank_code' => ['nullable', 'string', 'max:40'],
            'transaction_reference' => ['required', 'string', 'max:2000'],
        ], [
            'buyer_phone.regex' => 'Phone must be 9 digits after +251.',
            'transaction_reference.required' => 'Enter the bank transaction / receipt ID (or paste the full SMS).',
        ]);

        // Prevent obvious duplicate pending claims with same reference for same company.
        $duplicate = TicketOrder::query()
            ->where('company_id', $event->company_id)
            ->where('transaction_reference', $data['transaction_reference'])
            ->whereIn('status', [TicketOrder::STATUS_PENDING, TicketOrder::STATUS_PAID])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'transaction_reference' => 'This transaction ID was already submitted.',
            ])->withInput();
        }

        $attendee = $this->resolveAttendee($request, $data);

        try {
            $order = $orders->createPendingOrder($event, $data, $attendee);
        } catch (RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }

        $request->session()->put('public_attendee_id', $attendee->id);

        return redirect()->route('public.orders.show', $order->public_code)
            ->with('status', __('public.order_pending_title'));
    }

    public function show(string $publicCode)
    {
        $order = TicketOrder::query()
            ->with(['event.company', 'ticket'])
            ->where('public_code', $publicCode)
            ->firstOrFail();

        $this->ensureCanView($order);

        return view('public.orders.show', compact('order'));
    }

    private function resolveAttendee(Request $request, array $data): Attendee
    {
        $attendeeId = $request->session()->get('public_attendee_id');
        if ($attendeeId) {
            $attendee = Attendee::query()->find($attendeeId);
            if ($attendee) {
                $attendee->update([
                    'name' => $data['buyer_name'],
                    'phone' => $data['buyer_phone'],
                ]);

                return $attendee;
            }
        }

        return Attendee::create([
            'name' => $data['buyer_name'],
            'phone' => $data['buyer_phone'],
            'locale' => $request->session()->get('public_locale', 'en'),
        ]);
    }

    private function ensureCanView(TicketOrder $order): void
    {
        $attendeeId = session('public_attendee_id');
        if ($attendeeId && (int) $order->attendee_id === (int) $attendeeId) {
            return;
        }

        // Allow viewing by public code link (shared from SMS / Telegram).
    }
}
