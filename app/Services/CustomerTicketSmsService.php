<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySmsLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CompanySmsConfig;
use App\Support\SmsConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class CustomerTicketSmsService
{
    public function __construct(
        private readonly SmsVerificationService $sms,
        private readonly CompanySmsWalletService $wallet
    ) {}

    /**
     * Agent / counter sale: send when the org has an active SMS plan (credits left).
     */
    public function sendForTicketSale(Ticket $ticket): ?CompanySmsLog
    {
        return $this->sendForPurchase($ticket, 'agent_sale');
    }

    /**
     * Online / Telegram self-purchase after payment is confirmed.
     */
    public function sendForSelfPurchasePaid(Ticket $ticket): ?CompanySmsLog
    {
        return $this->sendForPurchase($ticket, 'online_purchase');
    }

    private function sendForPurchase(Ticket $ticket, string $source): ?CompanySmsLog
    {
        $ticket->loadMissing('event.company');
        $company = $ticket->event?->company;

        if ($company === null || ! $company->isPremium()) {
            return null;
        }

        if (! filled($ticket->buyer_phone)) {
            Log::info('Ticket SMS skipped: missing buyer phone', [
                'ticket_id' => $ticket->id,
                'source' => $source,
            ]);

            return null;
        }

        // Active SMS plan = remaining credits. Do not require auto_sms_on_sale for purchases.
        if (! $this->wallet->canSend($company)) {
            Log::warning('Ticket SMS skipped: SMS plan inactive or provider not ready', [
                'ticket_id' => $ticket->id,
                'company_id' => $company->id,
                'sms_credits' => $company->sms_credits,
                'has_api_key' => CompanySmsConfig::hasApiKey($company),
                'driver' => SmsConfig::driver(),
                'source' => $source,
            ]);

            return null;
        }

        return $this->sendTicketSaleMessage($ticket, $company);
    }

    private function sendTicketSaleMessage(Ticket $ticket, Company $company): CompanySmsLog
    {
        $message = $this->buildTicketMessage($ticket, $company);

        return $this->sendAndLog(
            $company,
            $ticket->buyer_phone,
            $message,
            CompanySmsLog::PURPOSE_TICKET_SALE,
            $ticket,
            null
        );
    }

    public function sendCustomMessage(Company $company, string $phone, string $message, User $sender): CompanySmsLog
    {
        if (! $company->isPremium()) {
            throw new RuntimeException('Premium plan required to message customers.');
        }

        if (! $this->wallet->canSend($company)) {
            throw new RuntimeException('Insufficient SMS credits. Purchase a package to continue.');
        }

        return $this->sendAndLog(
            $company,
            $phone,
            $message,
            CompanySmsLog::PURPOSE_CUSTOM,
            null,
            $sender
        );
    }

    private function sendAndLog(
        Company $company,
        string $phone,
        string $message,
        string $purpose,
        ?Ticket $ticket,
        ?User $sender
    ): CompanySmsLog {
        if (! CompanySmsConfig::canSend($company)) {
            throw new RuntimeException('SMS is not configured. Set the platform API key in Super Admin → SMS settings, or attach an org key.');
        }

        $log = CompanySmsLog::create([
            'company_id' => $company->id,
            'ticket_id' => $ticket?->id,
            'phone' => $phone,
            'message' => $message,
            'purpose' => $purpose,
            'status' => CompanySmsLog::STATUS_FAILED,
            'sent_by' => $sender?->id,
        ]);

        try {
            $costEtb = $company->sms_credits > 0
                ? (float) bcdiv((string) $company->wallet_balance_etb, (string) $company->sms_credits, 4)
                : 0;

            $this->sms->sendMessage($phone, $message, CompanySmsConfig::apiKey($company));

            $this->wallet->deductCredit(
                $company,
                $purpose === CompanySmsLog::PURPOSE_TICKET_SALE
                    ? "Ticket SMS to {$phone}"
                    : "Custom SMS to {$phone}",
                $ticket ?? $log,
                $sender,
                $costEtb
            );

            $log->update([
                'status' => CompanySmsLog::STATUS_SENT,
                'cost_etb' => $costEtb,
            ]);
        } catch (\Throwable $exception) {
            $log->update(['error_message' => $exception->getMessage()]);
            Log::error('Ticket SMS send failed', [
                'company_id' => $company->id,
                'ticket_id' => $ticket?->id,
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $log->fresh();
    }

    private function buildTicketMessage(Ticket $ticket, Company $company): string
    {
        $receiptLink = URL::signedRoute('tickets.public-receipt', ['ticket' => $ticket->id], now()->addDays(30));

        return str_replace(
            ['{buyer_name}', '{event}', '{ticket_code}', '{price}', '{currency}', '{organization}', '{receipt_link}'],
            [
                $ticket->buyer_name,
                $ticket->event->title ?? 'Event',
                $ticket->ticket_code,
                (string) $ticket->price_paid,
                $ticket->currency,
                $company->name,
                $receiptLink,
            ],
            $company->ticketSmsTemplate()
        );
    }
}
