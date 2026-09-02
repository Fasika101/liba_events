<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Premium Features</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active">Premium</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('error') }}</div>
    @endif

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box {{ $company->isPremium() ? 'bg-warning' : 'bg-secondary' }}">
                <span class="info-box-icon"><i class="fas fa-crown"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Current plan</span>
                    <span class="info-box-number text-capitalize">{{ $company->plan }}</span>
                </div>
            </div>
        </div>
        @if ($company->isPremium())
            <div class="col-md-4">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-sms"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">SMS credits left</span>
                        <span class="info-box-number">{{ number_format($company->sms_credits) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Wallet balance</span>
                        <span class="info-box-number">{{ number_format($company->wallet_balance_etb, 2) }} ETB</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($company->isStandard())
        <div class="card card-outline card-warning mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Upgrade to Premium</h3></div>
            <div class="card-body">
                <p class="text-muted">You are on the <strong>Standard</strong> plan. Premium unlocks customer SMS features for your organization:</p>
                <ul>
                    <li><strong>Auto SMS on ticket sale</strong> — customers receive ticket details and a receipt link when agents sell tickets</li>
                    <li><strong>Custom customer messages</strong> — send notifications and updates to your buyers via SMS</li>
                    <li><strong>SMS wallet</strong> — buy SMS packages; each successful message deducts 1 credit from your balance</li>
                </ul>
                @if ($company->premium_requested_at)
                    <div class="alert alert-info mb-0">
                        Premium requested on {{ $company->premium_requested_at->format('M j, Y') }}. Our team will enable Premium for your organization after review.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.premium.request') }}">
                        @csrf
                        <button type="submit" class="btn btn-warning"><i class="fas fa-crown mr-1"></i> Request Premium upgrade</button>
                    </form>
                @endif
            </div>
        </div>
    @else
        @if (! $company->canSendSms())
            <div class="alert alert-warning">
                <i class="fas fa-key mr-1"></i>
                Your organization does not have an SMS API key yet. Contact the platform administrator to enable sending.
                <a href="{{ route('admin.bulk-sms.index') }}" class="alert-link">Bulk SMS & sender ID</a>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-outline card-primary mb-4">
                    <div class="card-header"><h3 class="card-title mb-0">Ticket sale SMS settings</h3></div>
                    <form method="POST" action="{{ route('admin.premium.settings') }}">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="auto_sms_on_sale" name="auto_sms_on_sale" value="1"
                                       @checked(old('auto_sms_on_sale', $company->auto_sms_on_sale))>
                                <label class="custom-control-label" for="auto_sms_on_sale">Automatically SMS customers when agents complete a sale</label>
                            </div>
                            <div class="form-group mb-0">
                                <label for="ticket_sms_template">Message template</label>
                                <textarea name="ticket_sms_template" id="ticket_sms_template" rows="4" class="form-control"
                                          placeholder="{{ $company->defaultTicketSmsTemplate() }}">{{ old('ticket_sms_template', $company->ticket_sms_template) }}</textarea>
                                <small class="text-muted">Placeholders: {buyer_name}, {event}, {ticket_code}, {price}, {currency}, {organization}, {receipt_link}</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save settings</button>
                        </div>
                    </form>
                </div>

                <div class="card card-outline card-info mb-4">
                    <div class="card-header"><h3 class="card-title mb-0">Send custom SMS to a customer</h3></div>
                    <form method="POST" action="{{ route('admin.premium.custom-sms') }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Phone (+251…)</label>
                                <input type="text" name="phone" class="form-control" placeholder="+251912345678" value="{{ old('phone') }}" required>
                            </div>
                            <div class="form-group mb-0">
                                <label>Message</label>
                                <textarea name="message" rows="3" class="form-control" maxlength="480" required>{{ old('message') }}</textarea>
                                <small class="text-muted">Uses 1 SMS credit per successful send.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info" @disabled($company->sms_credits < 1 || ! $company->canSendSms())>
                                <i class="fas fa-paper-plane mr-1"></i> Send (1 credit)
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-outline card-success mb-4">
                    <div class="card-header"><h3 class="card-title mb-0">Buy SMS credits</h3></div>
                    <div class="card-body p-0">
                        @if ($pendingPurchase)
                            <div class="alert alert-warning m-3 mb-0">
                                Pending request: <strong>{{ $pendingPurchase->package->name }}</strong>
                                ({{ number_format($pendingPurchase->package->price_etb, 2) }} ETB)
                            </div>
                        @endif
                        <table class="table mb-0">
                            <thead><tr><th>Package</th><th>SMS</th><th>Price</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($packages as $package)
                                    <tr>
                                        <td>{{ $package->name }}</td>
                                        <td>{{ number_format($package->sms_count) }}</td>
                                        <td>{{ number_format($package->price_etb, 2) }} ETB<br><small class="text-muted">{{ number_format($package->price_per_sms, 2) }} ETB/SMS</small></td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.premium.purchase-package') }}">
                                                @csrf
                                                <input type="hidden" name="sms_package_id" value="{{ $package->id }}">
                                                <button type="submit" class="btn btn-sm btn-success">Request purchase</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No packages available yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-header"><h3 class="card-title mb-0">Recent SMS activity</h3></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($recentSms as $sms)
                                <li class="list-group-item small">
                                    <span class="badge badge-{{ $sms->status === 'sent' ? 'success' : 'danger' }}">{{ $sms->status }}</span>
                                    {{ $sms->phone }} — {{ Str::limit($sms->message, 60) }}
                                    <div class="text-muted">{{ $sms->created_at->format('M j, g:i A') }}</div>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center">No SMS sent yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
