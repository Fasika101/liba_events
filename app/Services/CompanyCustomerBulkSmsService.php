<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyBulkSmsLog;
use App\Models\CompanyBulkSmsLogItem;
use App\Models\CompanySmsLog;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CompanySmsConfig;
use App\Support\SmsConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompanyCustomerBulkSmsService
{
    public function __construct(
        private readonly SmsVerificationService $sms,
        private readonly CompanySmsWalletService $wallet,
        private readonly CustomerTicketSmsService $customerSms
    ) {}

    /**
     * @param  list<string>  $manualPhones
     * @param  list<string>  $crmPhones
     */
    public function send(
        Company $company,
        string $message,
        User $sender,
        array $manualPhones = [],
        array $crmPhones = [],
        ?int $eventId = null,
        ?string $title = null,
        bool $crmSelectAll = false
    ): CompanyBulkSmsLog {
        if (! $company->isPremium()) {
            throw new RuntimeException('Premium plan required.');
        }

        if (! CompanySmsConfig::canSend($company)) {
            throw new RuntimeException('SMS API key is not configured for your organization. Contact the platform administrator.');
        }

        if (! SmsConfig::isConfigured()) {
            throw new RuntimeException('SMS provider is not configured.');
        }

        $recipients = $this->resolveRecipients($company, $manualPhones, $crmPhones, $eventId, $crmSelectAll);

        if ($recipients->isEmpty()) {
            throw new RuntimeException('Add at least one recipient phone number.');
        }

        if ($company->sms_credits < $recipients->count()) {
            throw new RuntimeException("Insufficient SMS credits. You need {$recipients->count()} credits but have {$company->sms_credits}.");
        }

        return DB::transaction(function () use ($company, $message, $sender, $recipients, $title) {
            $log = CompanyBulkSmsLog::create([
                'company_id' => $company->id,
                'sent_by' => $sender->id,
                'title' => $title,
                'message' => $message,
            ]);

            $success = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($recipients as $recipient) {
                $phone = $recipient['phone'];
                $name = $recipient['name'] ?? null;
                $ticketId = $recipient['ticket_id'] ?? null;

                if (! $this->isValidPhone($phone)) {
                    $skipped++;
                    $this->recordItem($log, $phone, $name, $ticketId, CompanyBulkSmsLog::STATUS_SKIPPED, 'Invalid phone format.');

                    continue;
                }

                $personalized = $this->personalizeMessage($message, $company, $name, $recipient['event'] ?? null);

                try {
                    $this->customerSms->sendCustomMessage($company, $phone, $personalized, $sender);
                    $success++;
                    $this->recordItem($log, $phone, $name, $ticketId, CompanyBulkSmsLog::STATUS_SENT);
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->recordItem($log, $phone, $name, $ticketId, CompanyBulkSmsLog::STATUS_FAILED, $exception->getMessage());
                }
            }

            $log->update([
                'recipient_count' => $recipients->count(),
                'success_count' => $success,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
            ]);

            return $log->fresh(['items', 'sender']);
        });
    }

    /**
     * @param  list<string>  $manualPhones
     * @param  list<string>  $crmPhones
     */
    public function countRecipients(
        Company $company,
        array $manualPhones = [],
        array $crmPhones = [],
        ?int $eventId = null,
        bool $crmSelectAll = false
    ): int {
        return $this->resolveRecipients($company, $manualPhones, $crmPhones, $eventId, $crmSelectAll)->count();
    }

    public function countEventRecipients(Company $company, int $eventId): int
    {
        return app(SmsAccountingService::class)->countEventRecipients($company, $eventId);
    }

    /**
     * @param  list<string>  $manualPhones
     * @param  list<string>  $crmPhones
     * @return array{total: int, event_count: int, manual_count: int, crm_count: int}
     */
    public function previewRecipientBreakdown(
        Company $company,
        array $manualPhones = [],
        array $crmPhones = [],
        ?int $eventId = null,
        bool $crmSelectAll = false
    ): array {
        $eventCount = 0;
        if ($eventId !== null) {
            $eventCount = $this->countEventRecipients($company, $eventId);
        }

        $manualNormalized = [];
        foreach ($manualPhones as $phone) {
            $normalized = $this->normalizePhone(trim($phone));
            if ($normalized !== '') {
                $manualNormalized[$normalized] = true;
            }
        }

        if ($crmSelectAll) {
            $crmOnly = $this->countAllCustomers($company);
        } else {
            $crmOnly = 0;
            foreach ($crmPhones as $phone) {
                $normalized = $this->normalizePhone(trim($phone));
                if ($normalized !== '' && ! isset($manualNormalized[$normalized])) {
                    $crmOnly++;
                }
            }
        }

        $total = $this->countRecipients($company, $manualPhones, $crmPhones, $eventId, $crmSelectAll);

        return [
            'total' => $total,
            'event_count' => $eventCount,
            'manual_count' => count($manualNormalized),
            'crm_count' => $crmSelectAll ? $this->countAllCustomers($company) : $crmOnly,
            'crm_select_all' => $crmSelectAll,
        ];
    }

    /**
     * @param  list<string>  $manualPhones
     * @param  list<string>  $crmPhones
     * @return Collection<int, array{phone: string, name: ?string, ticket_id: ?int, event: ?string}>
     */
    private function resolveRecipients(
        Company $company,
        array $manualPhones,
        array $crmPhones,
        ?int $eventId,
        bool $crmSelectAll = false
    ): Collection {
        $byPhone = [];

        foreach ($manualPhones as $phone) {
            $normalized = $this->normalizePhone(trim($phone));
            if ($normalized !== '') {
                $byPhone[$normalized] = ['phone' => $normalized, 'name' => null, 'ticket_id' => null, 'event' => null];
            }
        }

        if ($crmSelectAll) {
            foreach ($this->allCustomers($company) as $customer) {
                $normalized = $customer['phone'];
                if ($normalized === '' || isset($byPhone[$normalized])) {
                    continue;
                }

                $byPhone[$normalized] = [
                    'phone' => $normalized,
                    'name' => $customer['name'],
                    'ticket_id' => null,
                    'event' => $customer['event'],
                ];
            }
        }

        foreach ($crmPhones as $phone) {
            $normalized = $this->normalizePhone(trim($phone));
            if ($normalized === '') {
                continue;
            }

            if (isset($byPhone[$normalized])) {
                continue;
            }

            $ticket = $this->latestTicketForPhone($company, $normalized);
            $byPhone[$normalized] = [
                'phone' => $normalized,
                'name' => $ticket?->buyer_name,
                'ticket_id' => $ticket?->id,
                'event' => $ticket?->event?->title,
            ];
        }

        if ($eventId !== null) {
            $event = Event::query()
                ->where('company_id', $company->id)
                ->findOrFail($eventId);

            $tickets = Ticket::query()
                ->where('event_id', $event->id)
                ->whereNotNull('buyer_phone')
                ->where('buyer_phone', '!=', '')
                ->get(['id', 'buyer_name', 'buyer_phone']);

            foreach ($tickets as $ticket) {
                $normalized = $this->normalizePhone($ticket->buyer_phone);
                if ($normalized === '') {
                    continue;
                }

                $byPhone[$normalized] = [
                    'phone' => $normalized,
                    'name' => $ticket->buyer_name,
                    'ticket_id' => $ticket->id,
                    'event' => $event->title,
                ];
            }
        }

        return collect(array_values($byPhone));
    }

    private function latestTicketForPhone(Company $company, string $phone): ?Ticket
    {
        return Ticket::query()
            ->where('buyer_phone', $phone)
            ->whereHas('event', fn ($q) => $q->withTrashed()->where('company_id', $company->id))
            ->latest('id')
            ->with('event')
            ->first();
    }

    private function personalizeMessage(string $message, Company $company, ?string $buyerName, ?string $eventTitle): string
    {
        return str_replace(
            ['{buyer_name}', '{organization}', '{event}', '{sender_id}', '{app_name}'],
            [$buyerName ?? 'Customer', $company->name, $eventTitle ?? '', $company->effectiveSenderId() ?? '', config('app.name')],
            $message
        );
    }

    private function recordItem(
        CompanyBulkSmsLog $log,
        string $phone,
        ?string $name,
        ?int $ticketId,
        string $status,
        ?string $error = null
    ): void {
        CompanyBulkSmsLogItem::create([
            'company_bulk_sms_log_id' => $log->id,
            'phone' => $phone,
            'recipient_name' => $name,
            'ticket_id' => $ticketId,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        if (preg_match('/^\+251[0-9]{9}$/', $phone)) {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '251')) {
            return '+'.$digits;
        }

        if (strlen($digits) === 9) {
            return '+251'.$digits;
        }

        return '';
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^\+251[0-9]{9}$/', $phone);
    }

    /**
     * @return Collection<int, array{phone: string, name: string, event: ?string}>
     */
    public function searchCustomers(Company $company, string $query, int $limit = 15): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return $this->customersQuery($company)
            ->where(function ($q) use ($query) {
                $q->where('tickets.buyer_name', 'like', "%{$query}%")
                    ->orWhere('tickets.buyer_phone', 'like', "%{$query}%");
            })
            ->orderBy('tickets.buyer_name')
            ->limit($limit)
            ->get(['tickets.buyer_name', 'tickets.buyer_phone', 'events.title as event_title'])
            ->map(fn ($row) => $this->mapCustomerRow($row));
    }

    /**
     * @return Collection<int, array{phone: string, name: string, event: ?string}>
     */
    public function allCustomers(Company $company, ?string $filter = null): Collection
    {
        $query = $this->customersQuery($company)->orderBy('tickets.buyer_name');

        if ($filter !== null && trim($filter) !== '') {
            $filter = trim($filter);
            $query->where(function ($q) use ($filter) {
                $q->where('tickets.buyer_name', 'like', "%{$filter}%")
                    ->orWhere('tickets.buyer_phone', 'like', "%{$filter}%");
            });
        }

        return $query
            ->get(['tickets.buyer_name', 'tickets.buyer_phone', 'events.title as event_title'])
            ->map(fn ($row) => $this->mapCustomerRow($row));
    }

    public function countAllCustomers(Company $company): int
    {
        $latestIds = Ticket::select(DB::raw('MAX(id) as id'))
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->whereHas('event', fn ($q) => $q->withTrashed()->where('company_id', $company->id))
            ->groupBy('buyer_phone');

        return Ticket::query()
            ->joinSub($latestIds, 'latest', fn ($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $company->id)
            ->count();
    }

    private function customersQuery(Company $company): \Illuminate\Database\Eloquent\Builder
    {
        $latestIds = Ticket::select(DB::raw('MAX(id) as id'))
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->whereHas('event', fn ($q) => $q->withTrashed()->where('company_id', $company->id))
            ->groupBy('buyer_phone');

        return Ticket::query()
            ->joinSub($latestIds, 'latest', fn ($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $company->id);
    }

    /**
     * @return array{phone: string, name: string, event: ?string}
     */
    private function mapCustomerRow(object $row): array
    {
        return [
            'phone' => $row->buyer_phone,
            'name' => $row->buyer_name,
            'event' => $row->event_title ?? null,
        ];
    }
}
