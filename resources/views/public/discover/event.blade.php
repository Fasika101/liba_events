@extends('layouts.marketplace')

@section('title', $event->title)

@section('content')
    <a class="mp-back" href="{{ route('public.discover') }}">← {{ __('public.back') }}</a>

    @php
        $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
        $accounts = $event->company->bankAccounts;
    @endphp

    <article class="mp-card" style="margin-bottom:16px;">
        <div class="mp-card-media" @if($photo) style="background-image:url('{{ $photo }}')" @endif></div>
        <div class="mp-card-body">
            <div class="mp-kicker">
                <a href="{{ route('public.organizers.show', $event->company) }}">{{ $event->company->name }}</a>
            </div>
            <h1 style="font-size:1.7rem;font-weight:800;margin:0 0 8px;line-height:1.15;letter-spacing:-.03em;color:#fff;">{{ $event->title }}</h1>
            <p class="mp-meta"><strong>{{ __('public.when') }}:</strong> {{ optional($event->start_at)->format('l, M j, Y · g:i A') }}</p>
            <p class="mp-meta"><strong>{{ __('public.price') }}:</strong>
                @if((float)$event->price <= 0) {{ __('public.free') }}
                @else {{ number_format($event->price, 2) }} {{ $event->currency }}
                @endif
            </p>
            @if($event->description)
                <h3 style="margin:14px 0 6px;font-size:1rem;">{{ __('public.about_event') }}</h3>
                <p style="margin:0;color:var(--mp-muted);white-space:pre-line;">{{ $event->description }}</p>
            @endif
        </div>
    </article>

    @if ($errors->any())
        <div class="mp-flash" style="background:rgba(155,28,28,.08);border-color:rgba(155,28,28,.25);color:#9b1c1c;">
            {{ $errors->first() }}
        </div>
    @endif

    @if($accounts->isEmpty())
        <div class="mp-empty">Payment accounts are not set for this organization yet.</div>
    @elseif($event->is_full)
        <div class="mp-empty">This event is full.</div>
    @else
        <section class="mp-card" style="margin-bottom:16px;">
            <div class="mp-card-body">
                <h2 style="margin-top:0;">{{ __('public.payment_accounts') }}</h2>
                <p class="mp-meta">{{ __('public.payment_hint') }}</p>
                @foreach ($accounts as $account)
                    <div class="mp-account">
                        <strong>{{ $account->bank_name }}</strong>
                        <div>{{ $account->account_name }}</div>
                        <code>{{ $account->account_number }}</code>
                        @if($account->instructions)
                            <div class="mp-meta" style="margin:0 0 8px;">{{ $account->instructions }}</div>
                        @endif
                        <button type="button" class="mp-btn mp-btn-secondary" style="min-height:40px;"
                                data-copy="{{ $account->copyText() }}"
                                data-copied-label="{{ __('public.copied') }}">
                            {{ __('public.copy') }}
                        </button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card-body">
                <h2 style="margin-top:0;">{{ __('public.your_details') }}</h2>
                <form method="POST" action="{{ route('public.events.checkout', $event) }}" class="mp-form">
                    @csrf
                    <div class="field">
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
                        <label>{{ __('public.yeneshaa_abat') }}</label>
                        <select name="yeneshaa_abat" required>
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
                    <div class="field">
                        <label for="transaction_reference">{{ __('public.transaction_id') }}</label>
                        <input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" required>
                        @error('transaction_reference') <div class="mp-error">{{ $message }}</div> @enderror
                    </div>
                    <button class="mp-btn mp-btn-block" type="submit">{{ __('public.submit_order') }}</button>
                </form>
            </div>
        </section>
    @endif
@endsection
