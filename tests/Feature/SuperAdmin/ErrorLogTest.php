<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\CompanySmsLog;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_sms_and_verify_error_logs(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_PREMIUM]);

        CompanySmsLog::create([
            'company_id' => $company->id,
            'phone' => '+251911223344',
            'message' => 'Hello',
            'purpose' => CompanySmsLog::PURPOSE_TICKET_SALE,
            'status' => CompanySmsLog::STATUS_FAILED,
            'error_message' => 'Provider rejected',
        ]);

        TicketOrder::create([
            'public_code' => 'ERRTEST01',
            'company_id' => $company->id,
            'event_id' => \App\Models\Event::create([
                'company_id' => $company->id,
                'title' => 'Gathering',
                'price' => 50,
                'currency' => 'ETB',
                'status' => 'active',
                'start_at' => now()->addDay(),
            ])->id,
            'buyer_name' => 'Abebe',
            'buyer_phone' => '+251911223344',
            'amount' => 50,
            'currency' => 'ETB',
            'status' => TicketOrder::STATUS_PENDING,
            'transaction_reference' => 'FT123',
            'verify_et_status' => 'error: amount mismatch',
        ]);

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('super-admin.error-logs.index', ['tab' => 'sms']))
            ->assertOk()
            ->assertSee('Provider rejected')
            ->assertSee('+251911223344');

        $this->actingAs($admin)
            ->get(route('super-admin.error-logs.index', ['tab' => 'verify-et']))
            ->assertOk()
            ->assertSee('amount mismatch')
            ->assertSee('ERRTEST01');
    }
}
