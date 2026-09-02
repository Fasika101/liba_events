<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySenderIdRequest;
use App\Models\CompanySmsLog;
use App\Models\CompanyWalletTransaction;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmsAccountingService
{
    /**
     * @return array{
     *     sender_id_revenue_etb: float,
     *     sender_id_count: int,
     *     sms_package_revenue_etb: float,
     *     sms_package_sales_count: int,
     *     sms_sent_count: int,
     *     sms_consumed_etb: float,
     *     total_revenue_etb: float
     * }
     */
    public function platformSummary(): array
    {
        $senderIdRevenue = (float) CompanySenderIdRequest::query()
            ->where('status', CompanySenderIdRequest::STATUS_APPROVED)
            ->sum('price_etb');

        $senderIdCount = CompanySenderIdRequest::query()
            ->where('status', CompanySenderIdRequest::STATUS_APPROVED)
            ->count();

        $smsPackageRevenue = (float) CompanyWalletTransaction::query()
            ->where('type', CompanyWalletTransaction::TYPE_TOPUP)
            ->sum('amount_etb');

        $smsPackageSalesCount = CompanyWalletTransaction::query()
            ->where('type', CompanyWalletTransaction::TYPE_TOPUP)
            ->count();

        $smsSentCount = CompanySmsLog::query()
            ->where('status', CompanySmsLog::STATUS_SENT)
            ->count();

        $smsConsumedEtb = (float) CompanySmsLog::query()
            ->where('status', CompanySmsLog::STATUS_SENT)
            ->sum('cost_etb');

        return [
            'sender_id_revenue_etb' => $senderIdRevenue,
            'sender_id_count' => $senderIdCount,
            'sms_package_revenue_etb' => $smsPackageRevenue,
            'sms_package_sales_count' => $smsPackageSalesCount,
            'sms_sent_count' => $smsSentCount,
            'sms_consumed_etb' => $smsConsumedEtb,
            'total_revenue_etb' => $senderIdRevenue + $smsPackageRevenue,
        ];
    }

    /**
     * @return Collection<int, object{
     *     company_id: int,
     *     company_name: string,
     *     sms_sent: int,
     *     sms_consumed_etb: float,
     *     package_revenue_etb: float,
     *     sender_id_revenue_etb: float,
     *     total_revenue_etb: float
     * }>
     */
    public function perCompanyBreakdown(): Collection
    {
        $smsSent = CompanySmsLog::query()
            ->select('company_id', DB::raw('COUNT(*) as sms_sent'), DB::raw('COALESCE(SUM(cost_etb), 0) as sms_consumed_etb'))
            ->where('status', CompanySmsLog::STATUS_SENT)
            ->groupBy('company_id');

        $packageRevenue = CompanyWalletTransaction::query()
            ->select('company_id', DB::raw('COALESCE(SUM(amount_etb), 0) as package_revenue_etb'))
            ->where('type', CompanyWalletTransaction::TYPE_TOPUP)
            ->groupBy('company_id');

        $senderIdRevenue = CompanySenderIdRequest::query()
            ->select('company_id', DB::raw('COALESCE(SUM(price_etb), 0) as sender_id_revenue_etb'))
            ->where('status', CompanySenderIdRequest::STATUS_APPROVED)
            ->groupBy('company_id');

        return Company::query()
            ->leftJoinSub($smsSent, 'sms_stats', fn ($j) => $j->on('companies.id', '=', 'sms_stats.company_id'))
            ->leftJoinSub($packageRevenue, 'pkg_stats', fn ($j) => $j->on('companies.id', '=', 'pkg_stats.company_id'))
            ->leftJoinSub($senderIdRevenue, 'sid_stats', fn ($j) => $j->on('companies.id', '=', 'sid_stats.company_id'))
            ->where(function ($q) {
                $q->whereNotNull('sms_stats.company_id')
                    ->orWhereNotNull('pkg_stats.company_id')
                    ->orWhereNotNull('sid_stats.company_id');
            })
            ->orderBy('companies.name')
            ->get([
                'companies.id as company_id',
                'companies.name as company_name',
                DB::raw('COALESCE(sms_stats.sms_sent, 0) as sms_sent'),
                DB::raw('COALESCE(sms_stats.sms_consumed_etb, 0) as sms_consumed_etb'),
                DB::raw('COALESCE(pkg_stats.package_revenue_etb, 0) as package_revenue_etb'),
                DB::raw('COALESCE(sid_stats.sender_id_revenue_etb, 0) as sender_id_revenue_etb'),
            ])
            ->map(function ($row) {
                $row->total_revenue_etb = (float) $row->package_revenue_etb + (float) $row->sender_id_revenue_etb;

                return $row;
            });
    }

    /**
     * @return array{sms_sent: int, sms_consumed_etb: float, package_revenue_etb: float, sender_id_revenue_etb: float}
     */
    public function companySummary(Company $company): array
    {
        return [
            'sms_sent' => CompanySmsLog::query()
                ->where('company_id', $company->id)
                ->where('status', CompanySmsLog::STATUS_SENT)
                ->count(),
            'sms_consumed_etb' => (float) CompanySmsLog::query()
                ->where('company_id', $company->id)
                ->where('status', CompanySmsLog::STATUS_SENT)
                ->sum('cost_etb'),
            'package_revenue_etb' => (float) CompanyWalletTransaction::query()
                ->where('company_id', $company->id)
                ->where('type', CompanyWalletTransaction::TYPE_TOPUP)
                ->sum('amount_etb'),
            'sender_id_revenue_etb' => (float) CompanySenderIdRequest::query()
                ->where('company_id', $company->id)
                ->where('status', CompanySenderIdRequest::STATUS_APPROVED)
                ->sum('price_etb'),
        ];
    }

    public function countEventRecipients(Company $company, int $eventId): int
    {
        Event::query()
            ->where('company_id', $company->id)
            ->findOrFail($eventId);

        return (int) Ticket::query()
            ->where('event_id', $eventId)
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->distinct()
            ->count('buyer_phone');
    }
}
