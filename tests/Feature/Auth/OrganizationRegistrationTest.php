<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\OrganizationRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class OrganizationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['events.allow_organization_registration' => true]);
    }

    public function test_registration_page_can_be_rendered(): void
    {
        $this->get('/register-organization')
            ->assertOk()
            ->assertSeeVolt('pages.auth.register-organization');
    }

    public function test_registration_page_is_hidden_when_disabled(): void
    {
        config(['events.allow_organization_registration' => false]);

        $this->get('/register-organization')->assertNotFound();
    }

    public function test_login_page_shows_register_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Register and wait for approval');
    }

    public function test_organization_name_must_be_unique(): void
    {
        Company::factory()->create(['name' => 'Taken Org']);

        Volt::test('pages.auth.register-organization')
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('address', 'Addis Ababa')
            ->set('phone_digits', '911234567')
            ->set('admin_email', 'jane@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('organization_name', 'Taken Org')
            ->set('organization_address', 'Bole')
            ->set('organization_phone_digits', '922334455')
            ->call('sendVerificationCode')
            ->assertHasErrors(['organization_name']);
    }

    public function test_organization_phone_must_be_unique(): void
    {
        Company::factory()->create(['phone' => '+251911111111']);

        Volt::test('pages.auth.register-organization')
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('address', 'Addis Ababa')
            ->set('phone_digits', '911234567')
            ->set('admin_email', 'jane@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('organization_name', 'New Org')
            ->set('organization_address', 'Bole')
            ->set('organization_phone_digits', '911111111')
            ->call('sendVerificationCode')
            ->assertHasErrors(['organization_phone_digits']);
    }

    public function test_full_registration_flow_creates_pending_request(): void
    {
        $component = Volt::test('pages.auth.register-organization')
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->set('address', 'Addis Ababa')
            ->set('phone_digits', '911234567')
            ->set('admin_email', 'jane@neworg.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('organization_name', 'New Org')
            ->set('organization_address', 'Bole Road')
            ->set('organization_phone_digits', '922334455')
            ->call('sendVerificationCode')
            ->assertSet('step', 'verify');

        $request = OrganizationRegistrationRequest::find($component->get('registrationRequestId'));
        $this->assertNotNull($request);
        $this->assertNotNull($request->sms_verification_code);
        $this->assertSame(OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION, $request->status);
        $this->assertSame('jane@neworg.com', $request->admin_email);

        $component
            ->set('verification_code', $request->sms_verification_code)
            ->call('submitRegistration')
            ->assertSet('step', 'success');

        $request->refresh();
        $this->assertSame(OrganizationRegistrationRequest::STATUS_PENDING, $request->status);
        $this->assertNotNull($request->phone_verified_at);
    }

    public function test_super_admin_can_see_sms_code_on_index(): void
    {
        OrganizationRegistrationRequest::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address' => 'Addis',
            'phone' => '+251911234567',
            'admin_email' => 'jane@pending.org',
            'password' => 'password123',
            'organization_name' => 'Pending Org',
            'organization_address' => 'Bole',
            'organization_phone' => '+251922334455',
            'sms_verification_code' => '123456',
            'status' => OrganizationRegistrationRequest::STATUS_PENDING,
            'phone_verified_at' => now(),
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.registration-requests.index'))
            ->assertOk()
            ->assertSee('123456')
            ->assertSee('jane@pending.org');
    }

    public function test_super_admin_can_approve_registration(): void
    {
        $request = OrganizationRegistrationRequest::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address' => 'Addis',
            'phone' => '+251911234567',
            'admin_email' => 'jane@approved.org',
            'password' => 'password123',
            'organization_name' => 'Approved Org',
            'organization_address' => 'Bole',
            'organization_phone' => '+251922334455',
            'sms_verification_code' => '654321',
            'status' => OrganizationRegistrationRequest::STATUS_PENDING,
            'phone_verified_at' => now(),
        ]);

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.registration-requests.approve', $request))
            ->assertRedirect(route('super-admin.registration-requests.index'));

        $this->assertDatabaseHas('companies', [
            'name' => 'Approved Org',
            'phone' => '+251922334455',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@approved.org',
            'role' => 'admin',
            'name' => 'Jane Doe',
        ]);

        $request->refresh();
        $this->assertTrue($request->isApproved());
    }
}
