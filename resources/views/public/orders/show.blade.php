@extends('layouts.marketplace')

@section('title', $order->public_code)

@section('content')
    <a class="mp-back" href="{{ route('public.profile') }}">← {{ __('public.my_tickets') }}</a>

    <section class="mp-card">
        <div class="mp-card-body">
            @if($order->isPaid())
                <div class="mp-kicker">{{ __('public.status_paid') }}</div>
                <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 8px;letter-spacing:-.03em;color:#fff;">{{ __('public.order_paid_title') }}</h1>
                <p class="mp-meta">{{ $order->event?->title }} · <code>{{ $order->ticket?->ticket_code }}</code></p>
                <div class="mp-row" style="margin-top:16px;flex-wrap:wrap;">
                    <a class="mp-btn" href="{{ route('public.orders.ticket', $order->public_code) }}">{{ __('public.view_ticket') }}</a>
                    <a class="mp-btn mp-btn-secondary" href="{{ route('public.orders.ticket', $order->public_code) }}" target="_blank">{{ __('public.download_ticket') }}</a>
                </div>
            @elseif($order->status === 'rejected')
                <div class="mp-kicker">{{ __('public.status_rejected') }}</div>
                <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 8px;letter-spacing:-.03em;color:#fff;">{{ __('public.order_rejected_title') }}</h1>
                <p class="mp-meta">{{ $order->admin_note ?: $order->event?->title }}</p>
            @else
                <div class="mp-kicker">{{ __('public.status_pending') }}</div>
                <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 8px;letter-spacing:-.03em;color:#fff;">{{ __('public.order_pending_title') }}</h1>
                <p class="mp-meta">{{ __('public.order_pending_body') }}</p>
                <p class="mp-meta">{{ $order->event?->title }} · {{ number_format($order->amount, 2) }} {{ $order->currency }}</p>
                <p class="mp-meta">Txn: <code>{{ $order->transaction_reference }}</code></p>
            @endif
        </div>
    </section>
@endsection
