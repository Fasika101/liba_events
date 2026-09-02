<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySmsLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CompanySmsConfig;
use App\Support\SmsConfig;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class CustomerTicketSmsService
{
    public function __construct(
        private readonly SmsVerificationService $sms,
        private readonly CompanySmsWalletService $wallet
    ) {}

    public function sendForTicketSale(Ticket $ticket): ?CompanySmsLog
    {
        $ticket->loadMissing('event.company');
        $company = $ticket->event?->company;

        if ($company === null || ! $company->isPremium() || ! $company->auto_sms_on_sale) {
            return null;
        }

        if (! filled($ticket->buyer_phone)) {
            return null;
        }

        if (! $this->wallet->canSend($company)) {
            return null;
        }

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
            throw new RuntimeException('SMS API key is not configured for your organization.');
        }

        if (! SmsConfig::isConfigured()) {
            throw new RuntimeException('SMS provider is not configured.');
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
