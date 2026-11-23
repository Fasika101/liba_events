@extends('layouts.marketplace')

@section('title', __('public.onboarding_title'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.onboarding_title') }}</h1>
        <p>{{ __('public.onboarding_open_bot') }}</p>
    </section>

    <div class="mp-panel mp-onboard">
        @if(config('services.telegram.bot_username'))
            <a class="mp-btn mp-btn-block" href="https://t.me/{{ ltrim(config('services.telegram.bot_username'), '@') }}" target="_blank" rel="noopener">
                {{ __('public.continue_telegram') }}
            </a>
        @endif
        <p class="mp-meta" style="margin-top:14px;">{{ __('public.onboarding_need_telegram') }}</p>
    </div>
@endsection
