@extends('layouts.marketplace')

@section('title', $order->public_code)

@section('content')
    <a class="mp-back" href="{{ route('public.tickets') }}">← {{ __('public.my_tickets') }}</a>

    <section class="mp-card">
        <div class="mp-card-body">
            @if($order->isPaid())
                <div class="mp-kicker">{{ __('public.status_paid') }}</div>
                <h1 class="mp-display mp-display-sm">{{ __('public.order_paid_title') }}</h1>
                <p class="mp-meta">{{ $order->event?->title }} · <code>{{ $order->ticket?->ticket_code }}</code></p>
                <div class="mp-actions">
                    <a class="mp-btn mp-btn-action" href="{{ route('public.orders.ticket', $order->public_code) }}">
                        <i class="fas fa-eye"></i>
                        <span>{{ __('public.view_ticket') }}</span>
                    </a>
                    <a class="mp-btn mp-btn-secondary mp-btn-action" href="{{ route('public.orders.ticket', $order->public_code) }}" target="_blank" rel="noopener">
                        <i class="fas fa-download"></i>
                        <span>{{ __('public.download_ticket') }}</span>
                    </a>
                </div>
            @elseif($order->status === 'rejected')
                <div class="mp-kicker">{{ __('public.status_rejected') }}</div>
                <h1 class="mp-display mp-display-sm">{{ __('public.order_rejected_title') }}</h1>
                <p class="mp-meta">{{ $order->admin_note ?: $order->event?->title }}</p>
            @else
                <div class="mp-kicker">{{ __('public.status_pending') }}</div>
                <h1 class="mp-display mp-display-sm">{{ __('public.order_pending_title') }}</h1>
                <p class="mp-meta">{{ __('public.order_pending_body') }}</p>
                <p class="mp-meta">{{ $order->event?->title }} · {{ number_format($order->amount, 2) }} {{ $order->currency }}</p>
                <p class="mp-meta">Txn: <code>{{ $order->transaction_reference }}</code></p>
            @endif
        </div>
    </section>
@endsection
