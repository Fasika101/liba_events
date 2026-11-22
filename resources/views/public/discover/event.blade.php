@extends('layouts.marketplace')

@section('title', $event->title)

@section('content')
    <a class="mp-back" href="{{ route('public.discover') }}">← {{ __('public.back') }}</a>

    @php
        $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
        $accounts = $event->company->bankAccounts;
    @endphp

    <article class="mp-card mp-event-hero {{ $event->isExpired() ? 'is-expired' : '' }}">
        <div class="mp-card-media" @if($photo) style="background-image:url('{{ $photo }}')" @endif>
            @if($event->isExpired())
                <span class="mp-expired-ribbon">{{ __('public.expired') }}</span>
            @endif
        </div>
        <div class="mp-card-body">
            <div class="mp-row" style="margin-bottom:8px;">
                <div class="mp-kicker" style="margin:0;">
                    <a href="{{ route('public.organizers.show', $event->company) }}">{{ $event->company->name }}</a>
                </div>
                @if($event->isExpired())
                    <span class="mp-badge mp-badge-expired">{{ __('public.expired') }}</span>
                @endif
            </div>
            <h1 class="mp-display">{{ $event->title }}</h1>
            <div class="mp-event-facts">
                <div>
                    <span class="mp-fact-label">{{ __('public.when') }}</span>
                    <span class="mp-fact-value">{{ optional($event->start_at)->format('l, M j, Y · g:i A') }}</span>
                </div>
                <div>
                    <span class="mp-fact-label">{{ __('public.price') }}</span>
                    <span class="mp-fact-value mp-price">
                        @if((float)$event->price <= 0) {{ __('public.free') }}
                        @else {{ number_format($event->price, 2) }} {{ $event->currency }}
                        @endif
                    </span>
                </div>
            </div>
            @if($event->description)
                <div class="mp-event-about">
                    <h3 class="mp-section-title">{{ __('public.about_event') }}</h3>
                    <p class="mp-prose">{{ $event->description }}</p>
                </div>
            @endif
        </div>
    </article>

    @if ($errors->any())
        <div class="mp-flash" style="background:rgba(155,28,28,.08);border-color:rgba(155,28,28,.25);color:#fca5a5;">
            {{ $errors->first() }}
        </div>
    @endif

    @if($event->isExpired())
        <div class="mp-empty mp-empty-expired">{{ __('public.event_expired') }}</div>
    @elseif($accounts->isEmpty())
        <div class="mp-empty">Payment accounts are not set for this organization yet.</div>
    @elseif($event->is_full)
        <div class="mp-empty">This event is full.</div>
    @else
        <section class="mp-card mp-lux-panel">
            <div class="mp-card-body">
                <p class="mp-lux-eyebrow">01 — Transfer</p>
                <h2 class="mp-display mp-display-sm">{{ __('public.payment_accounts') }}</h2>
                <p class="mp-lux-hint">{{ __('public.payment_hint') }}</p>
                @foreach ($accounts as $account)
                    <div class="mp-account">
                        <div class="mp-account-head">
                            @if($account->resolvedLogoUrl())
                                <img class="mp-bank-logo" src="{{ $account->resolvedLogoUrl() }}" alt="{{ $account->bank_name }}" loading="lazy" referrerpolicy="no-referrer">
                            @else
                                <span class="mp-bank-logo mp-bank-logo-fallback" aria-hidden="true">{{ strtoupper(substr($account->bank_name, 0, 2)) }}</span>
                            @endif
                            <div>
                                <span class="mp-account-bank">{{ $account->bank_name }}</span>
                                <span class="mp-account-name">{{ $account->account_name }}</span>
                            </div>
                        </div>
                        <code class="mp-account-number">{{ $account->account_number }}</code>
                        @if($account->instructions)
                            <p class="mp-lux-hint" style="margin:0 0 10px;">{{ $account->instructions }}</p>
                        @endif
                        <button type="button" class="mp-btn mp-btn-secondary mp-btn-slim"
                                data-copy="{{ $account->copyText() }}"
                                data-copied-label="{{ __('public.copied') }}">
                            {{ __('public.copy') }}
                        </button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mp-card mp-lux-panel">
            <div class="mp-card-body">
                <p class="mp-lux-eyebrow">02 — Register</p>
                <h2 class="mp-display mp-display-sm">{{ __('public.your_details') }}</h2>

                <form method="POST" action="{{ route('public.events.checkout', $event) }}" class="mp-form mp-form-lux">
                    @csrf
                    <div class="mp-form-grid">
                        <div class="field field-span">
                            <label for="buyer_name">{{ __('public.full_name') }}</label>
                            <input id="buyer_name" name="buyer_name" value="{{ old('buyer_name') }}" required autocomplete="name">
                            @error('buyer_name') <div class="mp-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="buyer_phone">{{ __('public.phone') }}</label>
                            <input id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone', '+251') }}" required inputmode="tel" autocomplete="tel">
                            @error('buyer_phone') <div class="mp-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="buyer_email">{{ __('public.email') }}</label>
                            <input id="buyer_email" type="email" name="buyer_email" value="{{ old('buyer_email') }}" autocomplete="email">
                        </div>
                        <div class="field">
                            <label for="buyer_address">{{ __('public.address') }}</label>
                            <input id="buyer_address" name="buyer_address" value="{{ old('buyer_address') }}">
                        </div>
                        <div class="field">
                            <label for="buyer_occupation">{{ __('public.occupation') }}</label>
                            <input id="buyer_occupation" name="buyer_occupation" value="{{ old('buyer_occupation') }}">
                        </div>
                        <div class="field">
                            <label for="yeneshaa_abat">{{ __('public.yeneshaa_abat') }}</label>
                            <select id="yeneshaa_abat" name="yeneshaa_abat" required>
                                <option value="1" @selected(old('yeneshaa_abat') === '1')>{{ __('public.yes') }}</option>
                                <option value="0" @selected(old('yeneshaa_abat', '0') === '0')>{{ __('public.no') }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="bank_code">{{ __('public.bank') }}</label>
                            <select id="bank_code" name="bank_code">
                                <option value="">—</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->bank_code ?: $account->bank_name }}" @selected(old('bank_code') === ($account->bank_code ?: $account->bank_name))>
                                        {{ $account->bank_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field field-span">
                            <label for="transaction_reference">{{ __('public.transaction_id') }}</label>
                            <input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" required>
                            @error('transaction_reference') <div class="mp-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <button class="mp-btn mp-btn-block mp-btn-lux" type="submit">{{ __('public.submit_order') }}</button>
                </form>
            </div>
        </section>
    @endif
@endsection
