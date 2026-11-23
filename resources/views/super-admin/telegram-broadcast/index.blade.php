<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Telegram Broadcast</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Telegram Broadcast</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    @if (! $botConfigured)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Telegram bot token is not configured. Set <code>TELEGRAM_BOT_TOKEN</code> in <code>.env</code>.
        </div>
    @endif

    @if ($botConfigured && ! $channelConfigured)
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            Channel posting is disabled until you set <code>TELEGRAM_CHANNEL_ID</code> (and make the bot a channel admin).
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fab fa-telegram-plane mr-2"></i>Send update</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.telegram-broadcast.send') }}"
                      onsubmit="return confirm('Send this Telegram broadcast now?');">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="message_en">Message (English) <span class="text-danger">*</span></label>
                            <textarea name="message_en" id="message_en" rows="5"
                                      class="form-control @error('message_en') is-invalid @enderror"
                                      required maxlength="3500"
                                      placeholder="Write the update users should receive…">{{ old('message_en') }}</textarea>
                            @error('message_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="message_am">Message (Amharic, optional)</label>
                            <textarea name="message_am" id="message_am" rows="5"
                                      class="form-control @error('message_am') is-invalid @enderror"
                                      maxlength="3500"
                                      placeholder="አማርኛ መልእክት…">{{ old('message_am') }}</textarea>
                            <small class="form-text text-muted">
                                Amharic users get this version. The channel post includes both when Amharic is filled.
                            </small>
                            @error('message_am')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group mb-0">
                            <label class="d-block font-weight-bold">Send to</label>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="to_users" name="to_users" value="1"
                                       {{ old('to_users', '1') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="to_users">
                                    Registered Mini App users via bot
                                    <span class="text-muted">({{ number_format($usersEligible) }} eligible)</span>
                                </label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="to_channel" name="to_channel" value="1"
                                       {{ old('to_channel', $channelConfigured ? '1' : '') ? 'checked' : '' }}
                                       {{ $channelConfigured ? '' : 'disabled' }}>
                                <label class="custom-control-label" for="to_channel">
                                    Post to Telegram channel
                                    @if($channelId)
                                        <span class="text-muted">({{ $channelId }})</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $botConfigured ? '' : 'disabled' }}>
                            <i class="fas fa-paper-plane mr-1"></i> Broadcast now
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Setup checklist</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0 pl-3">
                        <li class="mb-2">Bot token: <strong>{{ $botConfigured ? 'OK' : 'Missing' }}</strong>
                            @if($botUsername)
                                <br><span class="text-muted">{{ '@'.ltrim($botUsername, '@') }}</span>
                            @endif
                        </li>
                        <li class="mb-2">Channel ID: <strong>{{ $channelConfigured ? 'OK' : 'Missing' }}</strong>
                            @if($channelUrl)
                                <br><a href="{{ $channelUrl }}" target="_blank" rel="noopener">Open channel</a>
                            @endif
                        </li>
                        <li>Bot must be a <strong>channel admin</strong> with permission to post messages.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
