<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\OrganizationRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrations_index_lists_manual_organizations(): void
    {
        $company = Company::factory()->create(['name' => 'Legacy Org', 'phone' => '+251911111111']);
        User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'legacy@example.com',
            'phone' => '+251922222222',
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.registration-requests.index'))
            ->assertOk()
            ->assertSee('Legacy Org')
            ->assertSee('legacy@example.com')
            ->assertSee('Manual setup');
    }

    public function test_super_admin_can_update_organization_admin_contacts(): void
    {
        $company = Company::factory()->create(['phone' => '+251911111111']);
        $admin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'email' => 'old@example.com',
            'phone' => '+251922222222',
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('super-admin.registration-requests.organizations.update', $company), [
                'admin_email' => 'new@example.com',
                'admin_phone_digits' => '933333333',
                'organization_phone_digits' => '944444444',
            ])
            ->assertRedirect(route('super-admin.registration-requests.index'));

        $admin->refresh();
        $company->refresh();

        $this->assertSame('new@example.com', $admin->email);
        $this->assertSame('+251933333333', $admin->phone);
        $this->assertSame('+251944444444', $company->phone);
    }

    public function test_super_admin_can_update_pending_registration_contacts(): void
    {
        $request = OrganizationRegistrationRequest::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address' => 'Addis',
            'phone' => '+251911111111',
            'admin_email' => 'old@example.com',
            'password' => 'password123',
            'organization_name' => 'Pending Org',
            'organization_address' => 'Bole',
            'organization_phone' => '+251922222222',
            'status' => OrganizationRegistrationRequest::STATUS_PENDING,
            'phone_verified_at' => now(),
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('super-admin.registration-requests.update', $request), [
                'admin_email' => 'updated@example.com',
                'phone_digits' => '933333333',
                'organization_phone_digits' => '944444444',
            ])
            ->assertRedirect(route('super-admin.registration-requests.show', $request));

        $request->refresh();

        $this->assertSame('updated@example.com', $request->admin_email);
        $this->assertSame('+251933333333', $request->phone);
        $this->assertSame('+251944444444', $request->organization_phone);
    }
}
