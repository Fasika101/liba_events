<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\BulkSmsLog;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BulkSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_bulk_sms_page(): void
    {
        Company::factory()->create(['name' => 'Org A', 'phone' => '+251911111111']);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.bulk-sms.index'))
            ->assertOk()
            ->assertSee('Bulk SMS')
            ->assertSee('Org A');
    }

    public function test_super_admin_can_send_bulk_sms_to_selected_organizations(): void
    {
        Http::fake([
            'smsethiopia.com/api/sms/send' => Http::response(['sent' => true, 'description' => 'Accepted for delivery'], 200),
        ]);

        Setting::set('sms.driver', 'smsethiopia');
        Setting::set('sms.api_key', 'test-key');

        $companyA = Company::factory()->create(['name' => 'Alpha Org', 'phone' => '+251911111111']);
        $companyB = Company::factory()->create(['name' => 'Beta Org', 'phone' => '+251922222222']);
        User::factory()->admin()->create([
            'company_id' => $companyA->id,
            'name' => 'Alice Admin',
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)
            ->post(route('super-admin.bulk-sms.send'), [
                'title' => 'Holiday greeting',
                'message' => 'Hello {admin_name}, happy holidays from {app_name}!',
                'company_ids' => [$companyA->id, $companyB->id],
            ]);

        $log = BulkSmsLog::first();
        $this->assertNotNull($log);
        $response->assertRedirect(route('super-admin.bulk-sms.show', $log));

        $this->assertSame(2, $log->success_count);
        $this->assertSame(0, $log->failed_count);
        $this->assertDatabaseCount('bulk_sms_log_items', 2);

        Http::assertSentCount(2);
    }

    public function test_organization_without_phone_is_skipped(): void
    {
        Setting::set('sms.driver', 'log');

        $withPhone = Company::factory()->create(['phone' => '+251911111111']);
        $withoutPhone = Company::factory()->create(['phone' => null]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.bulk-sms.send'), [
                'message' => 'Hello {organization}',
                'company_ids' => [$withPhone->id, $withoutPhone->id],
            ]);

        $log = BulkSmsLog::first();
        $this->assertSame(1, $log->success_count);
        $this->assertSame(1, $log->skipped_count);
    }
}
