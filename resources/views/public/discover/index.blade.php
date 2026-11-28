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

    @if($carouselAds->isNotEmpty())
        <section class="mp-ad-carousel mp-ad-carousel-compact" data-ad-carousel aria-label="{{ __('public.sponsored') }}">
            <div class="mp-ad-carousel-track">
                @foreach($carouselAds as $index => $ad)
                    @php
                        $tag = filled($ad->link_url) ? 'a' : 'div';
                        $href = filled($ad->link_url) ? ' href="'.e($ad->link_url).'" target="_blank" rel="noopener sponsored"' : '';
                        $image = $ad->imageUrl();
                    @endphp
                    <{{ $tag }} class="mp-ad-slide {{ $index === 0 ? 'is-active' : '' }}"{!! $href !!} data-ad-slide>
                        @if($image)
                            <span class="mp-ad-thumb">
                                <img src="{{ $image }}" alt="" decoding="async">
                            </span>
                        @endif
                        <span class="mp-ad-slide-copy">
                            <span class="mp-ad-label">{{ __('public.sponsored') }}</span>
                            <span class="mp-ad-slide-title">{{ $ad->title }}</span>
                            @if($ad->body)
                                <span class="mp-ad-slide-text">{{ $ad->body }}</span>
                            @endif
                        </span>
                    </{{ $tag }}>
                @endforeach
            </div>
            @if($carouselAds->count() > 1)
                <div class="mp-ad-carousel-dots" data-ad-dots>
                    @foreach($carouselAds as $index => $ad)
                        <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-ad-dot="{{ $index }}" aria-label="Ad {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <div class="mp-grid two">
        @forelse ($events as $index => $event)
            @php
                $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
                $price = (float) $event->price;
                $inlineEvery = 4;
                $showInline = $inlineAds->isNotEmpty()
                    && $index > 0
                    && ($index % $inlineEvery === 0);
                $inlineAd = $showInline ? $inlineAds[(($index / $inlineEvery) - 1) % $inlineAds->count()] : null;
            @endphp

            @if($inlineAd)
                @php
                    $tag = filled($inlineAd->link_url) ? 'a' : 'article';
                    $href = filled($inlineAd->link_url) ? ' href="'.e($inlineAd->link_url).'" target="_blank" rel="noopener sponsored"' : '';
                    $image = $inlineAd->imageUrl();
                @endphp
                <{{ $tag }} class="mp-inline-ad"{!! $href !!}>
                    @if($image)
                        <span class="mp-ad-thumb">
                            <img src="{{ $image }}" alt="" decoding="async">
                        </span>
                    @endif
                    <span class="mp-ad-slide-copy">
                        <span class="mp-ad-label">{{ __('public.sponsored') }}</span>
                        <span class="mp-ad-slide-title">{{ $inlineAd->title }}</span>
                        @if($inlineAd->body)
                            <span class="mp-ad-slide-text">{{ $inlineAd->body }}</span>
                        @endif
                    </span>
                </{{ $tag }}>
            @endif

            <a class="mp-card" href="{{ route('public.events.show', $event) }}">
                <div class="mp-card-media">
                    @if($photo)
                        <img class="mp-card-media-img" src="{{ $photo }}" alt="{{ $event->title }}" loading="lazy" decoding="async">
                    @endif
                </div>
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
