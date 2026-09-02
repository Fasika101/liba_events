<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CompanySenderIdRequest;
use App\Models\CompanyWalletTransaction;
use App\Services\SmsAccountingService;
use Illuminate\Http\Request;

class SmsAccountingController extends Controller
{
    public function index(SmsAccountingService $accounting)
    {
        $summary = $accounting->platformSummary();
        $companies = $accounting->perCompanyBreakdown();

        $recentSenderIdSales = CompanySenderIdRequest::query()
            ->with('company')
            ->where('status', CompanySenderIdRequest::STATUS_APPROVED)
            ->latest('processed_at')
            ->take(10)
            ->get();

        $recentPackageSales = CompanyWalletTransaction::query()
            ->with(['company', 'package'])
            ->where('type', CompanyWalletTransaction::TYPE_TOPUP)
            ->latest()
            ->take(10)
            ->get();

        return view('super-admin.accounting.index', compact(
            'summary',
            'companies',
            'recentSenderIdSales',
            'recentPackageSales'
        ));
    }
}
