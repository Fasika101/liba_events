@extends('layouts.marketplace')

@section('title', __('public.profile_title'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.profile_title') }}</h1>
        @if($attendee)
            <p>{{ $attendee->displayName() }}</p>
        @else
            <p>{{ __('public.continue_telegram') }} — open this page from the Telegram Mini App, or buy a ticket to start your profile.</p>
        @endif
    </section>

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
