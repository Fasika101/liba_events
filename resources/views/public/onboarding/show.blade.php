@extends('layouts.marketplace')

@section('title', __('public.onboarding_title'))

@section('content')
    <section class="mp-hero">
        <h1>{{ __('public.onboarding_title') }}</h1>
        <p>{{ __('public.onboarding_sub') }}</p>
    </section>

    <div class="mp-panel mp-onboard">
        <div class="mp-steps">
            <div class="mp-step {{ $needsChannel ? 'is-active' : 'is-done' }}">
                <span class="mp-step-num">1</span>
                <div>
                    <strong>{{ __('public.onboarding_step_channel') }}</strong>
                    <p>{{ __('public.onboarding_channel_hint') }}</p>
                </div>
            </div>
            <div class="mp-step {{ ! $needsChannel && $needsProfile ? 'is-active' : ($needsProfile ? '' : 'is-done') }}">
                <span class="mp-step-num">2</span>
                <div>
                    <strong>{{ __('public.onboarding_step_profile') }}</strong>
                    <p>{{ __('public.onboarding_profile_hint') }}</p>
                </div>
            </div>
        </div>

        @if($needsChannel)
            <div class="mp-onboard-block" id="channel-block">
                @if($channelUrl)
                    <a class="mp-btn mp-btn-block" href="{{ $channelUrl }}" target="_blank" rel="noopener" id="join-channel-btn">
                        {{ __('public.onboarding_join_channel') }}
                    </a>
                @endif
                <button type="button" class="mp-btn mp-btn-secondary mp-btn-block" id="check-channel-btn" style="margin-top:10px;">
                    {{ __('public.onboarding_i_joined') }}
                </button>
                <div class="mp-verify-status" id="channel-status" hidden></div>
            </div>
        @endif

        <div class="mp-onboard-block" id="profile-block" @if($needsChannel) hidden @endif>
            <form method="POST" action="{{ route('public.onboarding.register') }}" class="mp-form mp-form-lux" id="register-form">
                @csrf
                <div class="field">
                    <label for="name">{{ __('public.full_name') }}</label>
                    <input id="name" name="name" value="{{ old('name', $attendee->displayName()) }}" required autocomplete="name">
                    @error('name') <div class="mp-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="buyer_phone_local">{{ __('public.phone') }}</label>
                    <div class="mp-phone">
                        <span class="mp-phone-code">+251</span>
                        <input id="buyer_phone_local"
                               name="buyer_phone_local"
                               inputmode="numeric"
                               autocomplete="tel-national"
                               maxlength="9"
                               pattern="[0-9]{9}"
                               placeholder="9xxxxxxxx"
                               value="{{ old('buyer_phone_local', $attendee->phone ? preg_replace('/^\+251/', '', $attendee->phone) : '') }}"
                               required>
                    </div>
                    <p class="mp-meta">{{ __('public.phone_hint') }}</p>
                    @error('buyer_phone') <div class="mp-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="email">{{ __('public.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $attendee->email) }}" autocomplete="email">
                </div>

                <div class="field">
                    <label class="mp-check">
                        <input type="checkbox" name="notifications_enabled" value="1" {{ old('notifications_enabled', true) ? 'checked' : '' }}>
                        <span>{{ __('public.onboarding_notify') }}</span>
                    </label>
                </div>

                @error('channel') <div class="mp-error">{{ $message }}</div> @enderror

                <button type="submit" class="mp-btn mp-btn-block mp-btn-lux">{{ __('public.onboarding_finish') }}</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var phone = document.getElementById('buyer_phone_local');
    if (phone) {
        phone.addEventListener('input', function () {
            var v = phone.value.replace(/\D+/g, '');
            if (v.indexOf('0') === 0) v = v.slice(1);
            phone.value = v.slice(0, 9);
        });
    }

    var checkBtn = document.getElementById('check-channel-btn');
    var statusEl = document.getElementById('channel-status');
    var profileBlock = document.getElementById('profile-block');
    var channelBlock = document.getElementById('channel-block');
    var token = document.querySelector('meta[name="csrf-token"]');

    function setStatus(type, text) {
        if (!statusEl) return;
        statusEl.hidden = false;
        statusEl.className = 'mp-verify-status is-' + type;
        statusEl.textContent = text;
    }

    if (checkBtn) {
        checkBtn.addEventListener('click', function () {
            checkBtn.disabled = true;
            setStatus('pending', @json(__('public.onboarding_checking')));

            fetch(@json(route('public.onboarding.channel')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
              .then(function (res) {
                  checkBtn.disabled = false;
                  if (res.data.joined) {
                      setStatus('ok', @json(__('public.onboarding_channel_ok')));
                      if (channelBlock) channelBlock.hidden = true;
                      if (profileBlock) profileBlock.hidden = false;
                  } else {
                      setStatus('error', res.data.message || @json(__('public.onboarding_channel_not_joined')));
                  }
              }).catch(function () {
                  checkBtn.disabled = false;
                  setStatus('error', @json(__('public.onboarding_channel_not_joined')));
              });
        });
    }
})();
</script>
@endpush
