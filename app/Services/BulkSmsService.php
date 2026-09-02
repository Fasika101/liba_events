<?php

namespace App\Services;

use App\Models\BulkSmsLog;
use App\Models\BulkSmsLogItem;
use App\Models\Company;
use App\Models\User;
use App\Support\SmsConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BulkSmsService
{
    public function __construct(
        private readonly SmsVerificationService $sms
    ) {}

    /**
     * @param  list<int>  $companyIds
     */
    public function send(array $companyIds, string $message, ?string $title, User $sender): BulkSmsLog
    {
        if (! SmsConfig::isConfigured()) {
            throw new RuntimeException('SMS provider is not configured. Set it up under SMS settings first.');
        }

        $companies = Company::query()
            ->with('primaryAdmin')
            ->whereIn('id', $companyIds)
            ->orderBy('name')
            ->get();

        if ($companies->isEmpty()) {
            throw new RuntimeException('Select at least one organization to message.');
        }

        return DB::transaction(function () use ($companies, $message, $title, $sender) {
            $log = BulkSmsLog::create([
                'title' => $title,
                'message' => $message,
                'sent_by' => $sender->id,
            ]);

            $success = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($companies as $company) {
                $phone = $company->smsPhone();
                $personalized = $this->personalizeMessage($message, $company);

                if (! filled($phone)) {
                    $skipped++;
                    $this->recordItem($log, $company, null, BulkSmsLog::STATUS_SKIPPED, 'No organization or admin phone number on file.');

                    continue;
                }

                try {
                    $this->sms->sendMessage($phone, $personalized);
                    $success++;
                    $this->recordItem($log, $company, $phone, BulkSmsLog::STATUS_SENT);
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->recordItem($log, $company, $phone, BulkSmsLog::STATUS_FAILED, $exception->getMessage());
                }
            }

            $log->update([
                'total_count' => $companies->count(),
                'success_count' => $success,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
            ]);

            return $log->fresh(['items', 'sender']);
        });
    }

    private function personalizeMessage(string $message, Company $company): string
    {
        $adminName = $company->primaryAdmin?->name ?? 'there';

        return str_replace(
            ['{organization}', '{admin_name}', '{app_name}'],
            [$company->name, $adminName, config('app.name')],
            $message
        );
    }

    private function recordItem(
        BulkSmsLog $log,
        Company $company,
        ?string $phone,
        string $status,
        ?string $error = null
    ): void {
        BulkSmsLogItem::create([
            'bulk_sms_log_id' => $log->id,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'phone' => $phone,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    /**
     * @return Collection<int, Company>
     */
    public function organizationsForMessaging(): Collection
    {
        return Company::query()
            ->with('primaryAdmin')
            ->orderBy('name')
            ->get();
    }
}
