@extends('layouts.marketplace')

@section('title', __('public.gate_title'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.gate_title') }}</h1>
        <p>{{ __('public.gate_sub') }}</p>
    </section>

    <div class="mp-panel mp-onboard">
        <p class="mp-meta" style="margin-top:0;">{{ __('public.gate_waiting') }}</p>

        @if($botUsername)
            <a class="mp-btn mp-btn-block" href="https://t.me/{{ ltrim($botUsername, '@') }}" target="_blank" rel="noopener">
                {{ __('public.continue_telegram') }}
            </a>
        @endif

        <p class="mp-meta" style="margin-top:14px;">{{ __('public.onboarding_open_bot') }}</p>
    </div>
@endsection
