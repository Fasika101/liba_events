<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrganizationRegistrationRequest;
use App\Models\User;
use App\Rules\EthiopianPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegistrationRequestController extends Controller
{
    public function index()
    {
        $requests = OrganizationRegistrationRequest::query()
            ->latest()
            ->paginate(20, ['*'], 'requests_page');

        $organizations = Company::query()
            ->with(['primaryAdmin', 'approvedRegistrationRequest'])
            ->latest()
            ->paginate(20, ['*'], 'orgs_page');

        $pendingCount = OrganizationRegistrationRequest::query()
            ->where('status', OrganizationRegistrationRequest::STATUS_PENDING)
            ->count();

        $awaitingSmsCount = OrganizationRegistrationRequest::query()
            ->where('status', OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION)
            ->count();

        $manualOrgCount = Company::query()
            ->whereDoesntHave('approvedRegistrationRequest')
            ->count();

        return view('super-admin.registration-requests.index', compact(
            'requests',
            'organizations',
            'pendingCount',
            'awaitingSmsCount',
            'manualOrgCount'
        ));
    }

    public function show(OrganizationRegistrationRequest $registrationRequest)
    {
        $registrationRequest->load('reviewer');

        return view('super-admin.registration-requests.show', [
            'request' => $registrationRequest,
        ]);
    }

    public function update(OrganizationRegistrationRequest $registrationRequest, Request $request)
    {
        if (! $registrationRequest->isAwaitingReview()) {
            return back()->with('error', 'Only pending applications can be edited.');
        }

        $activeStatuses = [
            OrganizationRegistrationRequest::STATUS_PENDING,
            OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION,
        ];

        $data = $request->validate([
            'admin_email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('organization_registration_requests', 'admin_email')
                    ->where(fn ($query) => $query->whereIn('status', $activeStatuses))
                    ->ignore($registrationRequest->id),
            ],
            'phone_digits' => ['required', 'regex:/^[0-9]{9}$/'],
            'organization_phone_digits' => [
                'required',
                'regex:/^[0-9]{9}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($registrationRequest, $activeStatuses): void {
                    $phone = EthiopianPhone::normalize((string) $value);

                    if (Company::where('phone', $phone)->exists()) {
                        $fail('This organization phone number is already registered.');
                    }

                    $exists = OrganizationRegistrationRequest::query()
                        ->where('organization_phone', $phone)
                        ->whereIn('status', $activeStatuses)
                        ->where('id', '!=', $registrationRequest->id)
                        ->exists();

                    if ($exists) {
                        $fail('Another registration already uses this organization phone number.');
                    }
                },
            ],
        ]);

        $registrationRequest->update([
            'admin_email' => $data['admin_email'],
            'phone' => EthiopianPhone::normalize($data['phone_digits']),
            'organization_phone' => EthiopianPhone::normalize($data['organization_phone_digits']),
        ]);

        return redirect()->route('super-admin.registration-requests.show', $registrationRequest)
            ->with('status', 'Registration application updated.');
    }

    public function editOrganization(Company $company)
    {
        $company->load('primaryAdmin');

        return view('super-admin.registration-requests.edit-organization', [
            'company' => $company,
            'admin' => $company->primaryAdmin,
        ]);
    }

    public function updateOrganization(Company $company, Request $request)
    {
        $admin = $company->primaryAdmin;

        if ($admin === null) {
            return back()->with('error', 'This organization has no admin user. Add one from Company admins first.');
        }

        $data = $request->validate([
            'admin_email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($admin->id),
            ],
            'admin_phone_digits' => ['nullable', 'regex:/^[0-9]{9}$/'],
            'organization_phone_digits' => [
                'nullable',
                'regex:/^[0-9]{9}$/',
                Rule::unique('companies', 'phone')->ignore($company->id),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $phone = EthiopianPhone::normalize((string) $value);

                    if (OrganizationRegistrationRequest::query()
                        ->where('organization_phone', $phone)
                        ->whereIn('status', [
                            OrganizationRegistrationRequest::STATUS_PENDING,
                            OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION,
                        ])
                        ->exists()) {
                        $fail('A pending registration already uses this organization phone number.');
                    }
                },
            ],
        ]);

        $admin->update([
            'email' => $data['admin_email'],
            'phone' => filled($data['admin_phone_digits'])
                ? EthiopianPhone::normalize($data['admin_phone_digits'])
                : null,
        ]);

        $company->update([
            'phone' => filled($data['organization_phone_digits'])
                ? EthiopianPhone::normalize($data['organization_phone_digits'])
                : null,
        ]);

        return redirect()->route('super-admin.registration-requests.index')
            ->with('status', "Contact details updated for \"{$company->name}\".");
    }

    public function approve(Request $request, OrganizationRegistrationRequest $registrationRequest)
    {
        if (! $registrationRequest->isPending()) {
            return back()->with('error', 'This registration request has already been reviewed or is still awaiting SMS verification.');
        }

        if (empty($registrationRequest->admin_email) || empty($registrationRequest->password)) {
            return back()->with('error', 'This registration is missing login credentials.');
        }

        if (User::where('email', $registrationRequest->admin_email)->exists()) {
            return back()->withErrors(['admin_email' => 'An account with this email already exists.']);
        }

        if (Company::where('name', $registrationRequest->organization_name)->exists()) {
            return back()->withErrors(['organization_name' => 'An organization with this name already exists.']);
        }

        if (Company::where('phone', $registrationRequest->organization_phone)->exists()) {
            return back()->withErrors(['organization_phone' => 'An organization with this phone number already exists.']);
        }

        DB::transaction(function () use ($registrationRequest) {
            $company = Company::create([
                'name' => $registrationRequest->organization_name,
                'address' => $registrationRequest->organization_address,
                'phone' => $registrationRequest->organization_phone,
            ]);

            User::create([
                'company_id' => $company->id,
                'name' => $registrationRequest->fullName(),
                'email' => $registrationRequest->admin_email,
                'phone' => $registrationRequest->phone,
                'password' => $registrationRequest->password,
                'role' => 'admin',
            ]);

            $registrationRequest->update([
                'company_id' => $company->id,
                'status' => OrganizationRegistrationRequest::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'rejection_reason' => null,
            ]);
        });

        return redirect()->route('super-admin.registration-requests.index')
            ->with('status', 'Registration approved. Organization and admin account created.');
    }

    public function reject(Request $request, OrganizationRegistrationRequest $registrationRequest)
    {
        if (! $registrationRequest->isAwaitingReview()) {
            return back()->with('error', 'This registration request has already been reviewed.');
        }

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $registrationRequest->update([
            'status' => OrganizationRegistrationRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return redirect()->route('super-admin.registration-requests.index')
            ->with('status', 'Registration request rejected.');
    }

    public function destroy(OrganizationRegistrationRequest $registrationRequest)
    {
        $organizationName = $registrationRequest->organization_name;

        $registrationRequest->delete();

        return redirect()->route('super-admin.registration-requests.index')
            ->with('status', "Registration request for \"{$organizationName}\" has been deleted.");
    }
}
