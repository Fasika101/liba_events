<?php

use App\Models\Company;
use App\Models\OrganizationRegistrationRequest;
use App\Rules\EthiopianPhone;
use App\Services\SmsVerificationService;
use App\Support\SmsConfig;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-wide')] class extends Component
{
    public string $step = 'form';

    public ?int $registrationRequestId = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $address = '';
    public string $phone_digits = '';
    public string $admin_email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $organization_name = '';
    public string $organization_address = '';
    public string $organization_phone_digits = '';
    public string $verification_code = '';

    public function sendVerificationCode(SmsVerificationService $sms): void
    {
        $this->validate($this->formRules());

        $phone = EthiopianPhone::normalize($this->phone_digits);
        $organizationPhone = EthiopianPhone::normalize($this->organization_phone_digits);

        $code = $sms->sendOtp($organizationPhone);

        $request = OrganizationRegistrationRequest::updateOrCreate(
            [
                'organization_phone' => $organizationPhone,
                'status' => OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION,
            ],
            [
                'first_name' => trim($this->first_name),
                'last_name' => trim($this->last_name),
                'address' => trim($this->address),
                'phone' => $phone,
                'admin_email' => strtolower(trim($this->admin_email)),
                'password' => $this->password,
                'organization_name' => trim($this->organization_name),
                'organization_address' => trim($this->organization_address),
                'sms_verification_code' => $code,
                'phone_verified_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ]
        );

        $this->registrationRequestId = $request->id;
        $this->step = 'verify';
        $this->verification_code = '';
    }

    public function resendCode(SmsVerificationService $sms): void
    {
        if ($this->registrationRequestId === null) {
            return;
        }

        $organizationPhone = EthiopianPhone::normalize($this->organization_phone_digits);
        $code = $sms->sendOtp($organizationPhone);

        OrganizationRegistrationRequest::whereKey($this->registrationRequestId)->update([
            'sms_verification_code' => $code,
        ]);

        session()->flash('status', 'A new verification code has been sent.');
    }

    public function submitRegistration(SmsVerificationService $sms): void
    {
        $this->validate(array_merge($this->formRules(), [
            'verification_code' => ['required', 'string', 'size:'.SmsConfig::otpLength()],
        ]));

        $organizationPhone = EthiopianPhone::normalize($this->organization_phone_digits);

        if (! $sms->verifyOtp($organizationPhone, $this->verification_code)) {
            $this->addError('verification_code', 'Invalid or expired verification code. Please try again or request a new code.');

            return;
        }

        $request = OrganizationRegistrationRequest::query()
            ->whereKey($this->registrationRequestId)
            ->where('status', OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION)
            ->firstOrFail();

        $request->update([
            'status' => OrganizationRegistrationRequest::STATUS_PENDING,
            'phone_verified_at' => now(),
        ]);

        $this->step = 'success';
    }

    public function backToForm(): void
    {
        $this->step = 'form';
        $this->verification_code = '';
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    private function formRules(): array
    {
        $activeStatuses = [
            OrganizationRegistrationRequest::STATUS_PENDING,
            OrganizationRegistrationRequest::STATUS_PENDING_VERIFICATION,
        ];

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'phone_digits' => ['required', 'regex:/^[0-9]{9}$/'],
            'admin_email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('organization_registration_requests', 'admin_email')
                    ->where(fn ($query) => $query->whereIn('status', $activeStatuses))
                    ->ignore($this->registrationRequestId),
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'organization_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'name'),
                Rule::unique('organization_registration_requests', 'organization_name')
                    ->where(fn ($query) => $query->whereIn('status', $activeStatuses))
                    ->ignore($this->registrationRequestId),
            ],
            'organization_address' => ['required', 'string', 'max:500'],
            'organization_phone_digits' => [
                'required',
                'regex:/^[0-9]{9}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($activeStatuses): void {
                    $phone = EthiopianPhone::normalize((string) $value);

                    if (Company::where('phone', $phone)->exists()) {
                        $fail('This organization phone number is already registered.');
                    }

                    $query = OrganizationRegistrationRequest::query()
                        ->where('organization_phone', $phone)
                        ->whereIn('status', $activeStatuses);

                    if ($this->registrationRequestId) {
                        $query->where('id', '!=', $this->registrationRequestId);
                    }

                    if ($query->exists()) {
                        $fail('A registration already exists for this organization phone number.');
                    }
                },
            ],
        ];
    }
}; ?>

<div>
    <a href="{{ route('login') }}" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to sign in
    </a>

    @if (session('status'))
        <div class="alert-custom alert-info-custom">
            <i class="fas fa-info-circle mr-1"></i> {{ session('status') }}
        </div>
    @endif

    @if ($step === 'success')
        <div class="alert-custom alert-success-custom">
            <i class="fas fa-check-circle mr-1"></i>
            Your registration has been submitted successfully.
        </div>
        <p style="color:rgba(255,255,255,.75);font-size:.9rem;line-height:1.6;margin-bottom:20px;">
            Your application has been sent to our admin team for review. Once approved, you can sign in
            with the email and password you provided.
        </p>
        <a href="{{ route('login') }}" class="btn-login d-block text-center text-decoration-none">
            <i class="fas fa-arrow-right-to-bracket mr-2"></i> Return to sign in
        </a>
    @elseif ($step === 'verify')
        <div class="alert-custom alert-info-custom">
            <i class="fas fa-sms mr-1"></i>
            Enter the {{ SmsConfig::otpLength() }}-digit code sent to
            <strong>+251 {{ substr($organization_phone_digits, 0, 2) }} {{ substr($organization_phone_digits, 2, 3) }} {{ substr($organization_phone_digits, 5) }}</strong>
        </div>

        <form wire:submit="submitRegistration">
            <div class="form-group">
                <label for="verification_code">Verification code</label>
                <input wire:model="verification_code"
                       id="verification_code"
                       type="text"
                       inputmode="numeric"
                       maxlength="{{ SmsConfig::otpLength() }}"
                       class="form-control otp-input @error('verification_code') is-invalid @enderror"
                       placeholder="000000"
                       autofocus
                       autocomplete="one-time-code">
                @error('verification_code')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-login mb-2">
                <span wire:loading.remove wire:target="submitRegistration">
                    <i class="fas fa-paper-plane mr-2"></i> Submit registration
                </span>
                <span wire:loading wire:target="submitRegistration">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Submitting…
                </span>
            </button>

            <button type="button" wire:click="resendCode" class="btn-secondary-outline mb-2">
                <span wire:loading.remove wire:target="resendCode">
                    <i class="fas fa-redo mr-1"></i> Resend code
                </span>
                <span wire:loading wire:target="resendCode">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Sending…
                </span>
            </button>

            <button type="button" wire:click="backToForm" class="btn-secondary-outline">
                <i class="fas fa-edit mr-1"></i> Edit details
            </button>
        </form>
    @else
        <form wire:submit="sendVerificationCode">
            <p class="section-title"><i class="fas fa-user mr-1"></i> Your information</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name">First name</label>
                        <input wire:model="first_name" id="first_name" type="text"
                               class="form-control @error('first_name') is-invalid @enderror"
                               placeholder="First name" required autofocus>
                        @error('first_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name">Last name</label>
                        <input wire:model="last_name" id="last_name" type="text"
                               class="form-control @error('last_name') is-invalid @enderror"
                               placeholder="Last name" required>
                        @error('last_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Your address</label>
                <input wire:model="address" id="address" type="text"
                       class="form-control @error('address') is-invalid @enderror"
                       placeholder="Street, city" required>
                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="phone_digits">Your phone number</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">🇪🇹 +251</span>
                    </div>
                    <input wire:model="phone_digits" id="phone_digits" type="tel"
                           inputmode="numeric"
                           maxlength="9"
                           class="form-control @error('phone_digits') is-invalid @enderror"
                           placeholder="912345678" required>
                </div>
                <div class="help-text">9 digits after +251 (Ethiopian mobile)</div>
                @error('phone_digits')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <p class="section-title mt-3"><i class="fas fa-key mr-1"></i> Admin login credentials</p>

            <div class="form-group">
                <label for="admin_email">Admin email (for sign in)</label>
                <input wire:model="admin_email" id="admin_email" type="email"
                       class="form-control @error('admin_email') is-invalid @enderror"
                       placeholder="admin@yourorganization.com" required autocomplete="username">
                @error('admin_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input wire:model="password" id="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password_confirmation">Confirm password</label>
                        <input wire:model="password_confirmation" id="password_confirmation" type="password"
                               class="form-control"
                               placeholder="••••••••" required autocomplete="new-password">
                    </div>
                </div>
            </div>

            <p class="section-title mt-3"><i class="fas fa-building mr-1"></i> Organization</p>

            <div class="form-group">
                <label for="organization_name">Organization name</label>
                <input wire:model="organization_name" id="organization_name" type="text"
                       class="form-control @error('organization_name') is-invalid @enderror"
                       placeholder="Organization name" required>
                @error('organization_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="organization_address">Organization address</label>
                <input wire:model="organization_address" id="organization_address" type="text"
                       class="form-control @error('organization_address') is-invalid @enderror"
                       placeholder="Organization address" required>
                @error('organization_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="organization_phone_digits">Organization phone (SMS verification)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">🇪🇹 +251</span>
                    </div>
                    <input wire:model="organization_phone_digits" id="organization_phone_digits" type="tel"
                           inputmode="numeric"
                           maxlength="9"
                           class="form-control @error('organization_phone_digits') is-invalid @enderror"
                           placeholder="912345678" required>
                </div>
                <div class="help-text">We will send a verification code to this number</div>
                @error('organization_phone_digits')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn-login">
                <span wire:loading.remove wire:target="sendVerificationCode">
                    <i class="fas fa-sms mr-2"></i> Send verification code
                </span>
                <span wire:loading wire:target="sendVerificationCode">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Sending…
                </span>
            </button>
        </form>
    @endif
</div>
