@extends('layouts.marketplace')

@section('title', $company->name)

@section('content')
    <a class="mp-back" href="{{ route('public.organizers') }}">← {{ __('public.back') }}</a>

    <section class="mp-hero">
        <p class="mp-kicker">{{ __('public.organized_by') }}</p>
        <h1>{{ $company->name }}</h1>
        @if($company->address)
            <p>{{ $company->address }}</p>
        @endif
    </section>

    <div class="mp-grid">
        @forelse ($events as $event)
            @php $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null; @endphp
            <a class="mp-card" href="{{ route('public.events.show', $event) }}">
                <div class="mp-card-media" @if($photo) style="background-image:url('{{ $photo }}')" @endif></div>
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
        @empty
            <div class="mp-empty">{{ __('public.no_events') }}</div>
        @endforelse
    </div>
@endsection
