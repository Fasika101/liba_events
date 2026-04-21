<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public bool $saved = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->saved = true;
        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<div>
    <form wire:submit="updateProfileInformation">
        <div class="form-group">
            <label for="name">Name</label>
            <input wire:model="name" id="name" name="name" type="text"
                   class="form-control @error('name') is-invalid @enderror"
                   required autofocus autocomplete="name">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input wire:model="email" id="email" name="email" type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   required autocomplete="username">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2">
                    <span class="text-muted">Your email address is unverified.</span>
                    <button wire:click.prevent="sendVerification" class="btn btn-link btn-sm p-0">
                        Click here to re-send the verification email.
                    </button>
                    @if (session('status') === 'verification-link-sent')
                        <div class="text-success mt-1">A new verification link has been sent to your email address.</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center">
            <button type="submit" class="btn btn-primary">
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading><i class="fas fa-spinner fa-spin"></i></span>
            </button>
            <span wire:loading.remove class="ml-3 text-success" x-show="$wire.saved" style="display:none;">
                <i class="fas fa-check"></i> Saved.
            </span>
        </div>
    </form>
</div>
