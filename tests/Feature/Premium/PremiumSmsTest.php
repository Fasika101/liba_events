<?php

namespace Tests\Feature\Premium;

use App\Models\Company;
use App\Models\SmsPackage;
use App\Models\User;
use App\Services\CompanySmsWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumSmsTest extends TestCase
{
    use RefreshDatabase;

    private function premiumCompanyWithApi(array $extra = []): Company
    {
        return Company::factory()->create(array_merge([
            'plan' => Company::PLAN_PREMIUM,
            'sms_credits' => 5,
            'wallet_balance_etb' => 5,
            'auto_sms_on_sale' => true,
            'sms_api_key' => 'test-company-key',
        ], $extra));
    }

    public function test_auto_sms_on_ticket_sale_deducts_credit(): void
    {
        \App\Models\Setting::set('sms.driver', 'log');

        $company = $this->premiumCompanyWithApi();

        $agent = User::factory()->create(['company_id' => $company->id, 'role' => 'agent']);
        $event = \App\Models\Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'price' => 100,
            'currency' => 'ETB',
            'start_at' => now()->addDay(),
            'status' => 'active',
            'capacity' => 100,
        ]);

        $this->actingAs($agent)->post(route('agent.tickets.store'), [
            'event_id' => $event->id,
            'buyer_name' => 'Buyer Test',
            'buyer_phone' => '+251911234567',
            'yeneshaa_abat' => '0',
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame(4, $company->sms_credits);
        $this->assertDatabaseHas('company_sms_logs', [
            'company_id' => $company->id,
            'phone' => '+251911234567',
            'status' => 'sent',
            'purpose' => 'ticket_sale',
        ]);
    }

    public function test_wallet_topup_adds_credits(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_PREMIUM]);
        $package = SmsPackage::create([
            'name' => 'Test 100',
            'sms_count' => 100,
            'price_etb' => 100,
            'price_per_sms' => 1,
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->superAdmin()->create();

        app(CompanySmsWalletService::class)->topUpFromPackage($company, $package, $superAdmin);

        $company->refresh();
        $this->assertSame(100, $company->sms_credits);
        $this->assertEquals(100.0, (float) $company->wallet_balance_etb);
    }

    public function test_standard_plan_admin_sees_upgrade_page(): void
    {
        $company = Company::factory()->create(['plan' => Company::PLAN_STANDARD]);
        User::factory()->admin()->create(['company_id' => $company->id]);

        $this->actingAs(User::where('company_id', $company->id)->first())
            ->get(route('admin.premium.index'))
            ->assertOk()
            ->assertSee('Standard')
            ->assertSee('Request Premium');
    }

    public function test_sms_blocked_without_company_api_key(): void
    {
        \App\Models\Setting::set('sms.driver', 'log');

        $company = $this->premiumCompanyWithApi(['sms_api_key' => null, 'sms_credits' => 10]);
        $agent = User::factory()->create(['company_id' => $company->id, 'role' => 'agent']);
        $event = \App\Models\Event::create([
            'company_id' => $company->id,
            'title' => 'Test Event',
            'price' => 100,
            'currency' => 'ETB',
            'start_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $this->actingAs($agent)->post(route('agent.tickets.store'), [
            'event_id' => $event->id,
            'buyer_name' => 'Buyer Test',
            'buyer_phone' => '+251911234567',
            'yeneshaa_abat' => '0',
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame(10, $company->sms_credits);
        $this->assertDatabaseMissing('company_sms_logs', ['company_id' => $company->id]);
    }

    public function test_admin_can_send_bulk_sms_to_manual_numbers(): void
    {
        \App\Models\Setting::set('sms.driver', 'log');

        $company = $this->premiumCompanyWithApi(['sms_credits' => 10, 'wallet_balance_etb' => 10]);
        User::factory()->admin()->create(['company_id' => $company->id]);
        $admin = User::where('company_id', $company->id)->first();

        $this->actingAs($admin)->post(route('admin.bulk-sms.send'), [
            'message' => 'Hello from bulk test',
            'manual_phones' => "+251911111111\n+251922222222",
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame(8, $company->sms_credits);
        $this->assertDatabaseHas('company_bulk_sms_logs', ['company_id' => $company->id, 'success_count' => 2]);
    }

    public function test_bulk_sms_preview_count_for_event(): void
    {
        $company = $this->premiumCompanyWithApi();
        User::factory()->admin()->create(['company_id' => $company->id]);
        $admin = User::where('company_id', $company->id)->first();

        $event = \App\Models\Event::create([
            'company_id' => $company->id,
            'title' => 'Gala',
            'price' => 100,
            'currency' => 'ETB',
            'start_at' => now()->addDay(),
            'status' => 'active',
        ]);

        \App\Models\Ticket::create([
            'event_id' => $event->id,
            'agent_id' => User::factory()->create(['company_id' => $company->id, 'role' => 'agent'])->id,
            'buyer_name' => 'A',
            'buyer_phone' => '+251911111111',
            'price_paid' => 100,
            'currency' => 'ETB',
            'ticket_code' => 'T001',
            'sold_at' => now(),
            'yeneshaa_abat' => false,
        ]);

        \App\Models\Ticket::create([
            'event_id' => $event->id,
            'agent_id' => User::factory()->create(['company_id' => $company->id, 'role' => 'agent'])->id,
            'buyer_name' => 'B',
            'buyer_phone' => '+251922222222',
            'price_paid' => 100,
            'currency' => 'ETB',
            'ticket_code' => 'T002',
            'sold_at' => now(),
            'yeneshaa_abat' => false,
        ]);

        $this->actingAs($admin)->postJson(route('admin.bulk-sms.preview-count'), [
            'event_id' => $event->id,
        ])->assertOk()->assertJson([
            'total' => 2,
            'event_count' => 2,
        ]);
    }

    public function test_bulk_sms_crm_select_all_includes_all_customers(): void
    {
        \App\Models\Setting::set('sms.driver', 'log');

        $company = $this->premiumCompanyWithApi(['sms_credits' => 100, 'wallet_balance_etb' => 100]);
        User::factory()->admin()->create(['company_id' => $company->id]);
        $admin = User::where('company_id', $company->id)->first();
        $agent = User::factory()->create(['company_id' => $company->id, 'role' => 'agent']);

        $event = \App\Models\Event::create([
            'company_id' => $company->id,
            'title' => 'Show',
            'price' => 50,
            'currency' => 'ETB',
            'start_at' => now()->addDay(),
            'status' => 'active',
        ]);

        foreach (['+251911111111', '+251922222222', '+251933333333'] as $i => $phone) {
            \App\Models\Ticket::create([
                'event_id' => $event->id,
                'agent_id' => $agent->id,
                'buyer_name' => 'Buyer '.$i,
                'buyer_phone' => $phone,
                'price_paid' => 50,
                'currency' => 'ETB',
                'ticket_code' => 'T00'.$i,
                'sold_at' => now(),
                'yeneshaa_abat' => false,
            ]);
        }

        $this->actingAs($admin)->postJson(route('admin.bulk-sms.preview-count'), [
            'crm_select_all' => true,
        ])->assertOk()->assertJson([
            'total' => 3,
            'crm_select_all' => true,
        ]);
    }
}
