@extends('layouts.marketplace')

@section('title', __('public.organizers'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.organizers_title') }}</h1>
        <p>{{ __('public.organizers_sub') }}</p>
    </section>

    <form class="mp-search" method="GET" action="{{ route('public.organizers') }}">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('public.search_placeholder') }}">
        <button type="submit">{{ __('public.organizers') }}</button>
    </form>

    <div class="mp-grid">
        @forelse ($organizers as $org)
            @php $logo = $org->logoUrl(); @endphp
            <a class="mp-card mp-org-card" href="{{ route('public.organizers.show', $org) }}">
                <div class="mp-card-body mp-org-body">
                    <div class="mp-org-logo" aria-hidden="true">
                        @if($logo)
                            <img src="{{ $logo }}" alt="">
                        @else
                            <span>{{ $org->initials() }}</span>
                        @endif
                    </div>
                    <div class="mp-org-copy">
                        <div class="mp-kicker">{{ __('public.organized_by') }}</div>
                        <h2>{{ $org->name }}</h2>
                        <p class="mp-meta">
                            {{ $org->active_events_count }} {{ __('public.discover') }}
                            @if($org->address) · {{ $org->address }} @endif
                        </p>
                        <span class="mp-btn mp-btn-secondary" style="min-height:40px;padding:8px 14px;display:inline-flex;">{{ __('public.see_events') }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="mp-empty">{{ __('public.no_organizers') }}</div>
        @endforelse
    </div>

    @if($organizers->hasPages())
        <div class="mp-pagination">
            @if($organizers->onFirstPage())
                <span>‹</span>
            @else
                <a href="{{ $organizers->previousPageUrl() }}">‹</a>
            @endif
            @if($organizers->hasMorePages())
                <a href="{{ $organizers->nextPageUrl() }}">›</a>
            @else
                <span>›</span>
            @endif
        </div>
    @endif
@endsection
