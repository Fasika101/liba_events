@extends('layouts.marketplace')

@section('title', __('public.discover'))

@section('content')
    <section class="mp-hero">
        <p class="mp-kicker">{{ __('public.this_week') }}</p>
        <h1>{{ __('public.upcoming') }}</h1>
        <p>{{ __('public.upcoming_sub') }}</p>
    </section>

    <form class="mp-search" method="GET" action="{{ route('public.discover') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('public.search_placeholder') }}" enterkeyhint="search">
        <button type="submit">{{ __('public.discover') }}</button>
    </form>

    <div class="mp-grid two">
        @forelse ($events as $event)
            @php
                $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
                $price = (float) $event->price;
            @endphp
            <a class="mp-card" href="{{ route('public.events.show', $event) }}">
                <div class="mp-card-media" @if($photo) style="background-image:url('{{ $photo }}')" @endif></div>
                <div class="mp-card-body">
                    <div class="mp-kicker">{{ $event->company?->name }}</div>
                    <h2>{{ $event->title }}</h2>
                    <p class="mp-meta">{{ optional($event->start_at)->timezone(config('app.timezone'))->format('D, M j · g:i A') }}</p>
                    <div class="mp-row">
                        <span class="mp-price">
                            @if($price <= 0)
                                {{ __('public.free') }}
                            @else
                                {{ number_format($price, 2) }} {{ $event->currency }}
                            @endif
                        </span>
                        <span class="mp-meta" style="margin:0">
                            @if($event->spots_left !== null)
                                {{ __('public.spots_left', ['count' => $event->spots_left]) }}
                            @else
                                {{ __('public.unlimited') }}
                            @endif
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="mp-empty">{{ __('public.no_events') }}</div>
        @endforelse
    </div>

    @if($events->hasPages())
        <div class="mp-pagination">
            @if($events->onFirstPage())
                <span>‹</span>
            @else
                <a href="{{ $events->previousPageUrl() }}">‹</a>
            @endif
            @if($events->hasMorePages())
                <a href="{{ $events->nextPageUrl() }}">›</a>
            @else
                <span>›</span>
            @endif
        </div>
    @endif
@endsection
