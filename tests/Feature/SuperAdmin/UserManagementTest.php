<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\OrganizationRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_delete_company_admin(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->create(['company_id' => $company->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.users.destroy', $admin))
            ->assertRedirect(route('super-admin.company-admins.index'));

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_super_admin_can_delete_company(): void
    {
        $company = Company::factory()->create();
        User::factory()->admin()->create(['company_id' => $company->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.companies.destroy', $company))
            ->assertRedirect(route('super-admin.companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('users', ['company_id' => $company->id]);
    }

    public function test_super_admin_can_delete_registration_request(): void
    {
        $request = OrganizationRegistrationRequest::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address' => 'Addis',
            'phone' => '+251911234567',
            'admin_email' => 'jane@example.com',
            'password' => 'password123',
            'organization_name' => 'Remove Me Org',
            'organization_address' => 'Bole',
            'organization_phone' => '+251922334455',
            'status' => OrganizationRegistrationRequest::STATUS_PENDING,
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.registration-requests.destroy', $request))
            ->assertRedirect(route('super-admin.registration-requests.index'));

        $this->assertDatabaseMissing('organization_registration_requests', ['id' => $request->id]);
    }

    public function test_super_admin_cannot_delete_super_admin_user(): void
    {
        $target = User::factory()->superAdmin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('super-admin.company-admins.index'))
            ->delete(route('super-admin.users.destroy', $target))
            ->assertRedirect(route('super-admin.company-admins.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
