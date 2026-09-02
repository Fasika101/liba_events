<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Premium requests</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
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

    @if ($premiumUpgradeRequests->isNotEmpty())
        <div class="card card-outline card-warning mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Premium upgrade requests</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table mb-0">
                    <thead><tr><th>Organization</th><th>Requested</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($premiumUpgradeRequests as $org)
                            <tr>
                                <td><a href="{{ route('super-admin.companies.show', $org) }}">{{ $org->name }}</a></td>
                                <td>{{ $org->premium_requested_at->format('M j, Y g:i A') }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('super-admin.companies.premium.upgrade', $org) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Upgrade to Premium</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($senderIdRequests->isNotEmpty())
        <div class="card card-outline card-info mb-4">
            <div class="card-header"><h3 class="card-title mb-0">Custom sender ID requests</h3></div>
            <div class="card-body p-0">
                @foreach ($senderIdRequests as $req)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between flex-wrap mb-2">
                            <div>
                                <strong>{{ $req->company->name }}</strong> —
                                Sender ID: <code>{{ $req->requested_sender_id }}</code>
                                <span class="badge badge-secondary ml-1">{{ str_replace('_', ' ', $req->status) }}</span>
                            </div>
                            <small class="text-muted">{{ $req->created_at->format('M j, Y') }}</small>
                        </div>

                        @if ($req->status === 'pending')
                            <form method="POST" action="{{ route('super-admin.sender-id-requests.approve', $req) }}" class="mb-2">
                                @csrf
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-3 mb-2">
                                        <label class="small mb-0">Price (ETB)</label>
                                        <input type="number" name="price_etb" class="form-control form-control-sm" step="0.01" min="0" required
                                               value="{{ \App\Support\SmsConfig::senderIdPriceEtb() }}">
                                    </div>
                                    <div class="form-group col-md-7 mb-2">
                                        <label class="small mb-0">Payment instructions</label>
                                        <textarea name="payment_instructions" class="form-control form-control-sm" rows="2" required
                                                  placeholder="Pay to: Commercial Bank — Account 1000... Name: ..."></textarea>
                                    </div>
                                    <div class="form-group col-md-2 mb-2">
                                        <button type="submit" class="btn btn-sm btn-primary btn-block">Send payment info</button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('super-admin.sender-id-requests.reject', $req) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        @elseif ($req->status === 'payment_submitted')
                            @if ($req->payment_screenshot_path)
                                <p class="mb-2">
                                    <a href="{{ asset('storage/'.$req->payment_screenshot_path) }}" target="_blank" rel="noopener">View payment screenshot</a>
                                </p>
                            @endif
                            <form method="POST" action="{{ route('super-admin.sender-id-requests.activate', $req) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Verify payment & activate sender ID</button>
                            </form>
                            <form method="POST" action="{{ route('super-admin.sender-id-requests.reject', $req) }}" class="d-inline ml-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        @elseif ($req->status === 'awaiting_payment')
                            <p class="small text-muted mb-0">Waiting for organization to upload payment proof ({{ number_format($req->price_etb, 2) }} ETB).</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title mb-0">SMS package purchase requests</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Organization</th>
                        <th>Package</th>
                        <th>Requested by</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>{{ $item->company->name }}</td>
                            <td>{{ $item->package->name }} ({{ number_format($item->package->price_etb, 2) }} ETB)</td>
                            <td>{{ $item->requester->name ?? '—' }}</td>
                            <td><span class="badge badge-{{ $item->status === 'pending' ? 'warning' : ($item->status === 'approved' ? 'success' : 'secondary') }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->created_at->format('M j, Y') }}</td>
                            <td class="text-right text-nowrap">
                                @if ($item->status === 'pending')
                                    <form method="POST" action="{{ route('super-admin.premium.purchase-requests.approve', $item) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve & credit</button>
                                    </form>
                                    <form method="POST" action="{{ route('super-admin.premium.purchase-requests.reject', $item) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No package purchase requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif
    </div>
</x-app-layout>
