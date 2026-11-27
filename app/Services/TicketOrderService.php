<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TicketOrderService
{
    public function __construct(
        private readonly CustomerTicketSmsService $customerSms,
        private readonly VerifyEtClient $verifyEt,
    ) {}

    public function createPendingOrder(Event $event, array $buyer, ?Attendee $attendee = null): TicketOrder
    {
        $company = $event->company;

        if ($company === null || $company->isSuspended() || ! $company->isPremium()) {
            throw new RuntimeException('This organization is not available for public ticket sales.');
        }

        if ($event->status !== 'active') {
            throw new RuntimeException('This event is not open for purchase.');
        }

        if ($event->is_full) {
            throw new RuntimeException('This event is at full capacity.');
        }

        if ($event->isExpired()) {
            throw new RuntimeException('This event has ended. Registration is closed.');
        }

        if ($company->bankAccounts()->where('is_active', true)->count() < 1) {
            throw new RuntimeException('This organization has not added payment accounts yet.');
        }

        if ($attendee && filled($buyer['buyer_phone'] ?? null)) {
            $attendee->update(['phone' => $buyer['buyer_phone']]);
        }

        $order = TicketOrder::create([
            'company_id' => $company->id,
            'event_id' => $event->id,
            'attendee_id' => $attendee?->id,
            'buyer_name' => $buyer['buyer_name'],
            'buyer_email' => $buyer['buyer_email'] ?? null,
            'buyer_phone' => $buyer['buyer_phone'],
            'buyer_address' => $buyer['buyer_address'] ?? null,
            'buyer_occupation' => $buyer['buyer_occupation'] ?? null,
            'yeneshaa_abat' => (bool) (int) ($buyer['yeneshaa_abat'] ?? 0),
            'amount' => $event->price,
            'currency' => $event->currency ?: 'ETB',
            'status' => TicketOrder::STATUS_PENDING,
            'bank_code' => $buyer['bank_code'] ?? null,
            'transaction_reference' => $buyer['transaction_reference'] ?? null,
        ]);

        if (filled($order->transaction_reference) && $this->verifyEt->isConfigured()) {
            try {
                $this->submitVerifyEt($order);
            } catch (\Throwable $e) {
                Log::warning('Verify.et submit failed', ['order' => $order->id, 'error' => $e->getMessage()]);
                $order->update([
                    'verify_et_status' => 'error: '.$e->getMessage(),
                    'verify_et_payload' => json_encode(['error' => $e->getMessage()]),
                ]);
            }
        }

        return $order->fresh(['event', 'company', 'ticket']);
    }

    public function submitVerifyEt(TicketOrder $order): TicketOrder
    {
        $order->loadMissing('company.bankAccounts', 'event');

        $bank = strtolower((string) ($order->bank_code ?: 'cbe'));
        // If bank_code was a display name, keep as-is lowercase token.
        if (! in_array($bank, ['cbe', 'telebirr', 'cbebirr', 'boa', 'mpesa', 'dashen', 'awash', 'siinqee', 'kaafiebirr', 'coopayebirr'], true)) {
            $mapped = $order->company?->bankAccounts
                ?->firstWhere('bank_code', $order->bank_code)
                ?? $order->company?->bankAccounts?->firstWhere('bank_name', $order->bank_code);
            $bank = strtolower((string) ($mapped?->bank_code ?: 'cbe'));
        }

        $account = $order->company->bankAccounts
            ->where('is_active', true)
            ->when($order->bank_code, function ($c) use ($order) {
                return $c->filter(fn ($a) => $a->bank_code === $order->bank_code || $a->bank_name === $order->bank_code);
            })
            ->first()
            ?? $order->company->bankAccounts->where('is_active', true)->first();

        $result = $this->verifyEt->verify(
            $bank,
            (string) $order->transaction_reference,
            $account?->account_number,
            (float) $order->amount,
            $order->buyer_phone,
            'ticket-order-'.$order->id
        );

        $statusNote = $this->buildVerifyNote($result, (float) $order->amount);

        $order->update([
            'verify_et_request_id' => $result['request_id'],
            'verify_et_status' => $statusNote,
            'verify_et_payload' => json_encode($result['raw']),
        ]);

        if ($this->shouldAutoMarkPaid($result)) {
            return $this->markPaid($order->fresh(), null, 'Auto-verified via Verify.et (amount matched)');
        }

        return $order->fresh(['event', 'company', 'ticket']);
    }

    public function applyVerifyEtResult(TicketOrder $order, array $payload): TicketOrder
    {
        $result = $this->verifyEt->interpretResult($payload, (float) $order->amount);
        $statusNote = $this->buildVerifyNote($result, (float) $order->amount);

        $order->update([
            'verify_et_request_id' => $result['request_id'] ?: $order->verify_et_request_id,
            'verify_et_status' => $statusNote,
            'verify_et_payload' => json_encode($payload),
        ]);

        if ($order->isPending() && $this->shouldAutoMarkPaid($result)) {
            return $this->markPaid($order->fresh(), null, 'Auto-verified via Verify.et webhook (amount matched)');
        }

        return $order->fresh(['ticket', 'event']);
    }

    public function markPaid(TicketOrder $order, ?User $reviewer = null, ?string $note = null): TicketOrder
    {
        if ($order->isPaid()) {
            return $order->load(['ticket', 'event', 'attendee']);
        }

        if (! $order->isPending()) {
            throw new RuntimeException('Only pending orders can be marked as paid.');
        }

        $order = DB::transaction(function () use ($order, $reviewer, $note) {
            $order = TicketOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                return $order;
            }

            $event = Event::query()->whereKey($order->event_id)->lockForUpdate()->firstOrFail();
            $company = $event->company;

            if ($event->capacity > 0 && $event->tickets()->count() >= $event->capacity) {
                throw new RuntimeException('This event is at full capacity.');
            }

            $ticket = Ticket::create([
                'event_id' => $event->id,
                'agent_id' => null,
                'source' => 'self',
                'attendee_id' => $order->attendee_id,
                'ticket_order_id' => $order->id,
                'payment_reference' => $order->transaction_reference,
                'buyer_name' => $order->buyer_name,
                'buyer_email' => $order->buyer_email,
                'buyer_phone' => $order->buyer_phone,
                'buyer_address' => $order->buyer_address,
                'buyer_occupation' => $order->buyer_occupation,
                'yeneshaa_abat' => $order->yeneshaa_abat,
                'price_paid' => $order->amount,
                'currency' => $order->currency,
                'ticket_code' => Ticket::generateCode($event),
                'sold_at' => now(),
            ]);

            $order->update([
                'status' => TicketOrder::STATUS_PAID,
                'ticket_id' => $ticket->id,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            return $order->fresh(['ticket', 'event', 'attendee', 'company']);
        });

        try {
            $this->customerSms->sendForSelfPurchasePaid($order->ticket);
        } catch (\Throwable $e) {
            // Order is paid even if SMS fails — log so live issues are visible.
            \Illuminate\Support\Facades\Log::warning('Online purchase SMS failed after markPaid', [
                'order_id' => $order->id,
                'ticket_id' => $order->ticket_id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(TelegramNotifier::class)->notifyOrderPaid($order);
        } catch (\Throwable) {
            // Telegram is best-effort.
        }

        return $order;
    }

    public function markRejected(TicketOrder $order, User $reviewer, ?string $note = null): TicketOrder
    {
        if (! $order->isPending()) {
            throw new RuntimeException('Only pending orders can be rejected.');
        }

        $order->update([
            'status' => TicketOrder::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_note' => $note,
        ]);

        $order = $order->fresh(['attendee', 'event']);

        try {
            app(TelegramNotifier::class)->notifyOrderRejected($order);
        } catch (\Throwable) {
            // Telegram is best-effort.
        }

        return $order;
    }

    /**
     * Remove an online purchase ticket so capacity and revenue return,
     * while keeping the customer (attendee) and order buyer fields for records.
     */
    public function cancelPurchase(TicketOrder $order, User $reviewer, ?string $note = null): TicketOrder
    {
        if ($order->isCancelled()) {
            return $order->load(['event', 'attendee']);
        }

        if (! in_array($order->status, [TicketOrder::STATUS_PAID, TicketOrder::STATUS_PENDING, TicketOrder::STATUS_REJECTED], true)) {
            throw new RuntimeException('This order cannot be deleted.');
        }

        return DB::transaction(function () use ($order, $reviewer, $note) {
            $order = TicketOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isCancelled()) {
                return $order;
            }

            $ticketId = $order->ticket_id;
            $ticket = $ticketId
                ? Ticket::query()->whereKey($ticketId)->lockForUpdate()->first()
                : null;

            // Detach then delete the issued ticket so event capacity and revenue update.
            $order->update([
                'ticket_id' => null,
                'status' => TicketOrder::STATUS_CANCELLED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_note' => $note ?: ($order->admin_note ?: 'Online purchase deleted by organization admin.'),
            ]);

            if ($ticket) {
                // Keep attendee row; only remove this ticket sale.
                $ticket->delete();
            }

            return $order->fresh(['event', 'attendee']);
        });
    }

    private function shouldAutoMarkPaid(array $result): bool
    {
        if (! ($result['verified'] ?? false)) {
            return false;
        }

        // Require exact amount match when Verify.et returned an amount.
        if (($result['amount_matched'] ?? null) !== true) {
            return false;
        }

        // If settlement matching was evaluated and failed, do not auto-pay.
        if (($result['settlement_matched'] ?? null) === false) {
            return false;
        }

        return true;
    }

    private function buildVerifyNote(array $result, float $expectedAmount): string
    {
        $parts = [
            $result['status'] ?: 'pending',
        ];

        if ($result['amount'] !== null) {
            $parts[] = 'receipt='.$result['amount'].($result['currency'] ? ' '.$result['currency'] : '');
            $parts[] = 'expected='.$expectedAmount;
            $parts[] = ($result['amount_matched'] === true)
                ? 'amount_ok'
                : (($result['amount_matched'] === false) ? 'amount_mismatch' : 'amount_pending');
        } else {
            $parts[] = 'amount_pending';
        }

        if ($result['settlement_matched'] === true) {
            $parts[] = 'settlement_ok';
        } elseif ($result['settlement_matched'] === false) {
            $parts[] = 'settlement_mismatch';
        }

        return implode(' | ', $parts);
    }
}
