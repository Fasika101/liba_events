<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div>
    @if (session('status'))
        <div class="alert alert-success mb-3" style="background:rgba(16,185,129,.2);color:#6ee7b7;border:1px solid rgba(16,185,129,.3);border-radius:10px;">
            <i class="fas fa-check-circle mr-2"></i>{{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-3" style="background:rgba(239,68,68,.2);color:#fca5a5;border:1px solid rgba(239,68,68,.3);border-radius:10px;">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <form wire:submit="login">
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input wire:model="form.email"
                       id="email" type="email" name="email"
                       class="form-control @error('form.email') is-invalid @enderror"
                       placeholder="you@example.com"
                       required autofocus autocomplete="username">
            </div>
            @error('form.email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
                <input wire:model="form.password"
                       id="password" type="password" name="password"
                       class="form-control @error('form.password') is-invalid @enderror"
                       placeholder="••••••••"
                       required autocomplete="current-password">
            </div>
            @error('form.password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="custom-control custom-checkbox">
                <input wire:model="form.remember"
                       type="checkbox" class="custom-control-input" id="remember">
                <label class="custom-control-label" for="remember">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="color:rgba(255,255,255,.5);font-size:.78rem;font-weight:500;text-decoration:none;">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-login">
            <span wire:loading.remove>
                <i class="fas fa-arrow-right-to-bracket mr-2"></i> Sign In
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin mr-2"></i> Signing in…
            </span>
        </button>
    </form>

    @if (config('events.allow_organization_registration'))
        <div class="text-center mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.1);">
            <p style="color:rgba(255,255,255,.55);font-size:.82rem;margin-bottom:10px;">
                New organization?
            </p>
            <a href="{{ route('register-organization') }}"
               style="color:#93c5fd;font-size:.85rem;font-weight:600;text-decoration:none;">
                <i class="fas fa-building mr-1"></i> Register and wait for approval
            </a>
        </div>
    @endif
</div>
