<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">SMS settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">SMS settings</li>
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fas fa-sms mr-2"></i>Provider configuration</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.sms-settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-light border">
                            <strong>SMSEthiopia setup</strong>
                            <ol class="mb-0 pl-3 small text-muted">
                                <li>Sign up at <a href="https://smsethiopia.com/#/auth/sign-up" target="_blank" rel="noopener">smsethiopia.com</a></li>
                                <li>Buy or register your sender ID in the SMSEthiopia dashboard</li>
                                <li>Open <strong>Console → API Keys</strong> and generate an API key</li>
                                <li>Paste the API key below and choose <strong>SMSEthiopia</strong> as the provider</li>
                            </ol>
                            <div class="mt-2">
                                <a href="https://smsethiopia.com/#/landing/docs" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-book mr-1"></i> Open API documentation
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="driver">SMS provider</label>
                            <select name="driver" id="driver" class="form-control @error('driver') is-invalid @enderror" required>
                                <option value="log" @selected(old('driver', $settings['driver']) === 'log')>Log only (development — codes written to laravel.log)</option>
                                <option value="smsethiopia" @selected(old('driver', $settings['driver']) === 'smsethiopia')>SMSEthiopia (live SMS)</option>
                            </select>
                            @error('driver')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="api_key">API key</label>
                            <input type="password" name="api_key" id="api_key"
                                   class="form-control @error('api_key') is-invalid @enderror"
                                   placeholder="{{ $settings['has_api_key'] ? '•••••••••••••••• (leave blank to keep current key)' : 'Paste your SMSEthiopia API key' }}"
                                   autocomplete="off">
                            <small class="form-text text-muted">
                                From SMSEthiopia dashboard: <strong>Console → API Keys</strong>.
                                Sent in the <code>KEY</code> request header per their API.
                            </small>
                            @error('api_key')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="sender_id">Sender ID</label>
                            <input type="text" name="sender_id" id="sender_id"
                                   value="{{ old('sender_id', $settings['sender_id']) }}"
                                   class="form-control @error('sender_id') is-invalid @enderror"
                                   placeholder="e.g. LIBAEVENTS"
                                   maxlength="50">
                            <small class="form-text text-muted">
                                Your approved sender name from SMSEthiopia. It is linked to your account on their platform
                                (the API does not send this field — store it here for reference and use <code>{sender_id}</code> in your message template if needed).
                            </small>
                            @error('sender_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="sender_id_price_etb">Default custom sender ID price (ETB)</label>
                            <input type="number" name="sender_id_price_etb" id="sender_id_price_etb" min="0" step="0.01"
                                   value="{{ old('sender_id_price_etb', $settings['sender_id_price_etb']) }}"
                                   class="form-control @error('sender_id_price_etb') is-invalid @enderror"
                                   placeholder="e.g. 5000">
                            <small class="form-text text-muted">
                                Suggested price when approving organization custom sender ID requests. Super admin can override per request.
                            </small>
                            @error('sender_id_price_etb')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="message_template">Verification message template</label>
                            <textarea name="message_template" id="message_template" rows="3"
                                      class="form-control @error('message_template') is-invalid @enderror"
                                      required>{{ old('message_template', $settings['message_template']) }}</textarea>
                            <small class="form-text text-muted">
                                Placeholders: <code>{app_name}</code>, <code>{code}</code>, <code>{sender_id}</code>
                            </small>
                            @error('message_template')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="otp_length">Code length</label>
                                    <input type="number" name="otp_length" id="otp_length" min="4" max="8"
                                           value="{{ old('otp_length', $settings['otp_length']) }}"
                                           class="form-control @error('otp_length') is-invalid @enderror" required>
                                    @error('otp_length')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="otp_expiry_minutes">Code expiry (minutes)</label>
                                    <input type="number" name="otp_expiry_minutes" id="otp_expiry_minutes" min="1" max="60"
                                           value="{{ old('otp_expiry_minutes', $settings['otp_expiry_minutes']) }}"
                                           class="form-control @error('otp_expiry_minutes') is-invalid @enderror" required>
                                    @error('otp_expiry_minutes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title mb-0">Send test SMS</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.sms-settings.test') }}">
                    @csrf
                    <div class="card-body">
                        <p class="text-muted small">
                            Sends a test message using your saved settings. Ethiopian numbers only (+251, 9 digits).
                        </p>
                        <div class="form-group">
                            <label for="test_phone">Test phone number</label>
                            <input type="text" name="test_phone" id="test_phone"
                                   value="{{ old('test_phone') }}"
                                   class="form-control @error('test_phone') is-invalid @enderror"
                                   placeholder="+251912345678" required>
                            @error('test_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label for="test_message">Custom message (optional)</label>
                            <textarea name="test_message" id="test_message" rows="3"
                                      class="form-control @error('test_message') is-invalid @enderror"
                                      placeholder="Leave blank to use your verification template with code 123456">{{ old('test_message') }}</textarea>
                            @error('test_message')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane mr-1"></i> Send test
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">API reference</h3>
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2"><strong>Endpoint:</strong><br><code>POST https://smsethiopia.com/api/sms/send</code></p>
                    <p class="mb-2"><strong>Headers:</strong><br><code>KEY: your_api_key</code><br><code>Content-Type: application/json</code></p>
                    <p class="mb-0"><strong>Body:</strong><br><code>{"msisdn":"251911234567","text":"Your message"}</code></p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
