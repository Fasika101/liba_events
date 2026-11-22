@extends('layouts.marketplace')

@section('title', $event->title)

@section('content')
    <a class="mp-back" href="{{ route('public.discover') }}">← {{ __('public.back') }}</a>

    @php
        $photo = $event->photo_path ? asset('storage/'.$event->photo_path) : null;
        $accounts = $event->company->bankAccounts;
        $oldPhone = (string) old('buyer_phone', old('buyer_phone_local', ''));
        $phoneLocal = preg_replace('/\D+/', '', $oldPhone);
        if (str_starts_with($phoneLocal, '251') && strlen($phoneLocal) >= 12) {
            $phoneLocal = substr($phoneLocal, -9);
        } elseif (strlen($phoneLocal) > 9) {
            $phoneLocal = substr($phoneLocal, -9);
        }
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

                <form method="POST" action="{{ route('public.events.checkout', $event) }}" class="mp-form mp-form-lux" id="checkout-form"
                      data-verify-url="{{ route('public.events.verify-payment', $event) }}">
                    @csrf
                    <div class="mp-form-grid">
                        <div class="field field-span">
                            <label for="buyer_name">{{ __('public.full_name') }}</label>
                            <input id="buyer_name" name="buyer_name" value="{{ old('buyer_name') }}" required autocomplete="name">
                            @error('buyer_name') <div class="mp-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="buyer_phone_local">{{ __('public.phone') }}</label>
                            <div class="mp-phone">
                                <span class="mp-phone-code">+251</span>
                                <input id="buyer_phone_local"
                                       name="buyer_phone_local"
                                       value="{{ $phoneLocal }}"
                                       required
                                       inputmode="numeric"
                                       autocomplete="tel-national"
                                       maxlength="9"
                                       pattern="[0-9]{9}"
                                       placeholder="9XXXXXXXX">
                            </div>
                            <p class="mp-lux-hint" style="margin-top:6px;">{{ __('public.phone_hint') }}</p>
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
                            <textarea id="transaction_reference" name="transaction_reference" rows="3" required
                                      placeholder="Paste receipt ID, transaction number, or the full bank SMS">{{ old('transaction_reference') }}</textarea>
                            <div id="verify-status" class="mp-verify-status" aria-live="polite"></div>
                            <p class="mp-lux-hint" style="margin-top:6px;">
                                We check the receipt amount against {{ number_format((float) $event->price, 2) }} {{ $event->currency }} as you type.
                            </p>
                            @error('transaction_reference') <div class="mp-error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <button class="mp-btn mp-btn-block mp-btn-lux" type="submit" id="checkout-submit">{{ __('public.submit_order') }}</button>
                </form>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    var phone = document.getElementById('buyer_phone_local');
    if (phone) {
        phone.addEventListener('input', function () {
            var digits = phone.value.replace(/\D+/g, '').slice(0, 9);
            if (digits.charAt(0) === '0') {
                digits = digits.slice(1).slice(0, 9);
            }
            phone.value = digits;
        });
    }

    var form = document.getElementById('checkout-form');
    if (!form) return;

    var refInput = document.getElementById('transaction_reference');
    var bankInput = document.getElementById('bank_code');
    var statusEl = document.getElementById('verify-status');
    var verifyUrl = form.getAttribute('data-verify-url');
    var token = document.querySelector('meta[name="csrf-token"]');
    var timer = null;
    var lastKey = '';

    var i18n = {
        checking: @json(__('public.verify_checking')),
        needed: @json(__('public.verify_needed'))
    };

    function setStatus(state, message) {
        if (!statusEl) return;
        statusEl.className = 'mp-verify-status is-' + state;
        var icon = state === 'verified' ? '✓'
            : state === 'checking' ? '…'
            : state === 'pending' || state === 'manual' ? '•'
            : '!';
        statusEl.innerHTML = '<span class="mp-verify-icon">' + icon + '</span><span>' + message + '</span>';
    }

    function runVerify() {
        var reference = (refInput.value || '').trim();
        if (reference.length < 4) {
            setStatus('idle', i18n.needed);
            return;
        }

        var key = (bankInput.value || '') + '|' + reference;
        if (key === lastKey && statusEl.classList.contains('is-verified')) {
            return;
        }
        lastKey = key;

        setStatus('checking', i18n.checking);

        var phoneLocal = phone ? phone.value : '';
        var fullPhone = phoneLocal.length === 9 ? ('+251' + phoneLocal) : '';

        fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                bank_code: bankInput.value || '',
                transaction_reference: reference,
                buyer_phone: fullPhone
            })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            var state = (res.body && res.body.state) ? res.body.state : 'error';
            var message = (res.body && res.body.message) ? res.body.message : i18n.checking;
            if (state === 'verified') setStatus('verified', message);
            else if (state === 'pending' || state === 'manual') setStatus('pending', message);
            else setStatus('error', message);
        })
        .catch(function () {
            setStatus('error', @json(__('public.verify_error')));
        });
    }

    function scheduleVerify() {
        clearTimeout(timer);
        timer = setTimeout(runVerify, 900);
    }

    refInput.addEventListener('input', scheduleVerify);
    refInput.addEventListener('blur', runVerify);
    bankInput.addEventListener('change', runVerify);
})();
</script>
@endpush