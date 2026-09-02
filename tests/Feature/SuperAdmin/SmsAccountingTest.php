<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\CompanySenderIdRequest;
use App\Models\CompanyWalletTransaction;
use App\Models\User;
use App\Services\SmsAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsAccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_summary_totals_revenue(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_PREMIUM]);
        $superAdmin = User::factory()->superAdmin()->create();

        CompanyWalletTransaction::create([
            'company_id' => $company->id,
            'type' => CompanyWalletTransaction::TYPE_TOPUP,
            'amount_etb' => 1000,
            'sms_credits' => 1000,
            'sms_credits_after' => 1000,
            'wallet_balance_etb_after' => 1000,
            'description' => 'Test top-up',
            'created_by' => $superAdmin->id,
        ]);

        CompanySenderIdRequest::create([
            'company_id' => $company->id,
            'requested_sender_id' => 'MYBRAND11',
            'status' => CompanySenderIdRequest::STATUS_APPROVED,
            'price_etb' => 500,
            'processed_by' => $superAdmin->id,
            'processed_at' => now(),
        ]);

        $summary = app(SmsAccountingService::class)->platformSummary();

        $this->assertEquals(1000.0, $summary['sms_package_revenue_etb']);
        $this->assertEquals(500.0, $summary['sender_id_revenue_etb']);
        $this->assertEquals(1500.0, $summary['total_revenue_etb']);
    }

    public function test_super_admin_can_view_accounting_page(): void
    {
        User::factory()->superAdmin()->create();

        $this->actingAs(User::where('role', 'super_admin')->first())
            ->get(route('super-admin.accounting.index'))
            ->assertOk()
            ->assertSee('SMS Accounting');
    }
}
