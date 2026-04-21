<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<div>
    <form wire:submit="updatePassword">
        <div class="form-group">
            <label for="update_password_current_password">Current Password</label>
            <input wire:model="current_password" id="update_password_current_password" name="current_password"
                   type="password" class="form-control @error('current_password') is-invalid @enderror"
                   autocomplete="current-password">
            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="update_password_password">New Password</label>
            <input wire:model="password" id="update_password_password" name="password"
                   type="password" class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="update_password_password_confirmation">Confirm Password</label>
            <input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation"
                   type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                   autocomplete="new-password">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-warning">
            <span wire:loading.remove>Update Password</span>
            <span wire:loading><i class="fas fa-spinner fa-spin"></i></span>
        </button>
    </form>
</div>
