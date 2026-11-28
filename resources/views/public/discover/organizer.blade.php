@extends('layouts.marketplace')

@section('title', $company->name)

@section('content')
    <a class="mp-back" href="{{ route('public.organizers') }}">← {{ __('public.back') }}</a>

    <section class="mp-hero mp-org-hero">
        <div class="mp-org-logo mp-org-logo-lg" aria-hidden="true">
            @php $company->loadMissing('primaryAdmin'); @endphp
            @if($company->logoUrl())
                <img src="{{ $company->logoUrl() }}" alt="">
            @else
                <span>{{ $company->initials() }}</span>
            @endif
        </div>
        <div>
            <p class="mp-kicker">{{ __('public.organized_by') }}</p>
            <h1>{{ $company->name }}</h1>
            @if($company->address)
                <p>{{ $company->address }}</p>
            @endif
        </div>
    </section>

    <div class="mp-grid">
        @forelse ($events as $event)
            @php
                $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
                $expired = $event->isExpired();
            @endphp

            @if($expired)
                <div class="mp-card is-expired is-disabled" aria-disabled="true">
                    <div class="mp-card-media">
                        @if($photo)
                            <img class="mp-card-media-img" src="{{ $photo }}" alt="{{ $event->title }}" loading="lazy" decoding="async">
                        @endif
                        <span class="mp-expired-ribbon">{{ __('public.expired') }}</span>
                    </div>
                    <div class="mp-card-body">
                        <div class="mp-row" style="margin-bottom:6px;">
                            <h3 style="margin:0;">{{ $event->title }}</h3>
                            <span class="mp-badge mp-badge-expired">{{ __('public.expired') }}</span>
                        </div>
                        <p class="mp-meta">{{ optional($event->start_at)->format('D, M j · g:i A') }}</p>
                        <p class="mp-meta" style="margin:0;color:#cbd5e1;">{{ __('public.event_expired') }}</p>
                    </div>
                </div>
            @else
                <a class="mp-card" href="{{ route('public.events.show', $event) }}">
                    <div class="mp-card-media">
                        @if($photo)
                            <img class="mp-card-media-img" src="{{ $photo }}" alt="{{ $event->title }}" loading="lazy" decoding="async">
                        @endif
                    </div>
                    <div class="mp-card-body">
                        <h3>{{ $event->title }}</h3>
                        <p class="mp-meta">{{ optional($event->start_at)->format('D, M j · g:i A') }}</p>
                        <div class="mp-price">
                            @if((float)$event->price <= 0) {{ __('public.free') }}
                            @else {{ number_format($event->price, 2) }} {{ $event->currency }}
                            @endif
                        </div>
                    </div>
                </a>
            @endif
        @empty
            <div class="mp-empty">{{ __('public.no_events') }}</div>
        @endforelse
    </div>
@endsection
