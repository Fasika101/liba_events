<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

        if (filled($order->transaction_reference) && filled($order->bank_code) && $this->verifyEt->isConfigured()) {
            try {
                $settlement = $company->bankAccounts()
                    ->where('is_active', true)
                    ->when($order->bank_code, fn ($q) => $q->where('bank_code', $order->bank_code))
                    ->value('account_number');

                $result = $this->verifyEt->verify(
                    (string) $order->bank_code,
                    (string) $order->transaction_reference,
                    $settlement ? (string) $settlement : null
                );

                $order->update([
                    'verify_et_request_id' => $result['request_id'],
                    'verify_et_status' => $result['status'],
                    'verify_et_payload' => json_encode($result['raw']),
                ]);
            } catch (\Throwable) {
                // Org can still verify manually.
            }
        }

        return $order->fresh(['event', 'company']);
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
        } catch (\Throwable) {
            // Order is paid even if SMS fails.
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

        return $order->fresh();
    }
}
