<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyWalletTransaction;
use App\Models\SmsPackage;
use App\Models\User;
use App\Support\CompanySmsConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompanySmsWalletService
{
    public function topUpFromPackage(Company $company, SmsPackage $package, User $actor): CompanyWalletTransaction
    {
        return DB::transaction(function () use ($company, $package, $actor) {
            $company->lockForUpdate();
            $company->refresh();

            $company->sms_credits += $package->sms_count;
            $company->wallet_balance_etb = bcadd((string) $company->wallet_balance_etb, (string) $package->price_etb, 2);
            $company->save();

            return CompanyWalletTransaction::create([
                'company_id' => $company->id,
                'type' => CompanyWalletTransaction::TYPE_TOPUP,
                'amount_etb' => $package->price_etb,
                'sms_credits' => $package->sms_count,
                'sms_credits_after' => $company->sms_credits,
                'wallet_balance_etb_after' => $company->wallet_balance_etb,
                'description' => "Top-up: {$package->name} ({$package->sms_count} SMS)",
                'sms_package_id' => $package->id,
                'created_by' => $actor->id,
            ]);
        });
    }

    public function deductCredit(
        Company $company,
        string $description,
        ?Model $reference = null,
        ?User $actor = null,
        float $costEtb = 0
    ): CompanyWalletTransaction {
        return DB::transaction(function () use ($company, $description, $reference, $actor, $costEtb) {
            $company->lockForUpdate();
            $company->refresh();

            if ($company->sms_credits < 1) {
                throw new RuntimeException('Insufficient SMS credits.');
            }

            $costEtb = $costEtb > 0
                ? $costEtb
                : (float) bcdiv((string) $company->wallet_balance_etb, (string) max($company->sms_credits, 1), 4);

            $company->sms_credits -= 1;
            if ($costEtb > 0) {
                $company->wallet_balance_etb = max(0, (float) bcsub((string) $company->wallet_balance_etb, (string) $costEtb, 2));
            }
            $company->save();

            return CompanyWalletTransaction::create([
                'company_id' => $company->id,
                'type' => CompanyWalletTransaction::TYPE_DEDUCTION,
                'amount_etb' => -abs($costEtb),
                'sms_credits' => -1,
                'sms_credits_after' => $company->sms_credits,
                'wallet_balance_etb_after' => $company->wallet_balance_etb,
                'description' => $description,
                'created_by' => $actor?->id,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    public function canSend(Company $company): bool
    {
        return $company->isPremium()
            && $company->sms_credits > 0
            && CompanySmsConfig::hasApiKey($company);
    }
}
