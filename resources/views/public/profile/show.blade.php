@extends('layouts.marketplace')

@section('title', __('public.profile_title'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.profile_title') }}</h1>
        @if($attendee)
            <p>{{ $attendee->displayName() }}</p>
        @else
            <p>{{ __('public.continue_telegram') }} — {{ __('public.onboarding_need_telegram') }}</p>
        @endif
    </section>

    @if($attendee && $attendee->isOnboarded())
        <div class="mp-panel" style="margin-bottom:18px;">
            <h2 class="mp-section-title">{{ __('public.profile_settings') }}</h2>
            <form method="POST" action="{{ route('public.profile.update') }}" class="mp-form mp-form-lux">
                @csrf
                <div class="field">
                    <label for="name">{{ __('public.full_name') }}</label>
                    <input id="name" name="name" value="{{ old('name', $attendee->name) }}" required>
                </div>
                <div class="field">
                    <label for="buyer_phone_local">{{ __('public.phone') }}</label>
                    <div class="mp-phone">
                        <span class="mp-phone-code">+251</span>
                        @php
                            $phoneLocal = old('buyer_phone_local', $attendee->phone ? preg_replace('/^\+251/', '', (string) $attendee->phone) : '');
                        @endphp
                        <input id="buyer_phone_local" name="buyer_phone_local" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" value="{{ $phoneLocal }}" required>
                    </div>
                </div>
                <div class="field">
                    <label for="email">{{ __('public.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $attendee->email) }}">
                </div>
                <div class="field">
                    <label class="mp-check">
                        <input type="checkbox" name="notifications_enabled" value="1" {{ old('notifications_enabled', $attendee->notifications_enabled) ? 'checked' : '' }}>
                        <span>{{ __('public.onboarding_notify') }}</span>
                    </label>
                </div>
                <button type="submit" class="mp-btn mp-btn-block">{{ __('public.save_profile') }}</button>
            </form>
        </div>
    @endif

    <div class="mp-grid">
        @forelse ($orders as $order)
            <a class="mp-card" href="{{ route('public.orders.show', $order->public_code) }}">
                <div class="mp-card-body">
                    <div class="mp-row" style="margin-bottom:8px;">
                        <span class="mp-kicker" style="margin:0;">{{ $order->event?->company?->name }}</span>
                        <span class="mp-badge mp-badge-{{ $order->status }}">
                            {{ __('public.status_'.$order->status) }}
                        </span>
                    </div>
                    <h3>{{ $order->event?->title }}</h3>
                    <p class="mp-meta">{{ number_format($order->amount, 2) }} {{ $order->currency }} · {{ $order->created_at?->format('M j, Y') }}</p>
                    @if($order->isPaid() && $order->ticket)
                        <div class="mp-price">{{ $order->ticket->ticket_code }}</div>
                    @endif
                </div>
            </a>
        @empty
            <div class="mp-empty">{{ __('public.profile_empty') }}</div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var phone = document.getElementById('buyer_phone_local');
    if (!phone) return;
    phone.addEventListener('input', function () {
        var v = phone.value.replace(/\D+/g, '');
        if (v.indexOf('0') === 0) v = v.slice(1);
        phone.value = v.slice(0, 9);
    });
})();
</script>
@endpush
