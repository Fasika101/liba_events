<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Order {{ $order->public_code }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.online-orders.index') }}">Online Orders</a></li>
                    <li class="breadcrumb-item active">{{ $order->public_code }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('status')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Buyer & payment</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Event</dt><dd class="col-sm-8">{{ $order->event?->title }}</dd>
                        <dt class="col-sm-4">Buyer</dt><dd class="col-sm-8">{{ $order->buyer_name }}</dd>
                        <dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $order->buyer_phone }}</dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $order->buyer_email ?: '—' }}</dd>
                        <dt class="col-sm-4">Yeneshaa Abat</dt><dd class="col-sm-8">{{ $order->yeneshaa_abat ? 'Yes' : 'No' }}</dd>
                        <dt class="col-sm-4">Amount</dt><dd class="col-sm-8"><strong>{{ number_format($order->amount, 2) }} {{ $order->currency }}</strong></dd>
                        <dt class="col-sm-4">Bank</dt><dd class="col-sm-8">{{ $order->bank_code ?: '—' }}</dd>
                        <dt class="col-sm-4">Transaction ID</dt><dd class="col-sm-8"><code>{{ $order->transaction_reference ?: '—' }}</code></dd>
                        <dt class="col-sm-4">Verify.et</dt>
                        <dd class="col-sm-8">
                            <code>{{ $order->verify_et_status ?: '—' }}</code>
                            @if($order->verify_et_request_id)
                                <div class="text-muted small mt-1">Request: {{ $order->verify_et_request_id }}</div>
                            @endif
                            <div class="text-muted small mt-1">
                                Auto-pay only when receipt amount matches order amount
                                ({{ number_format($order->amount, 2) }} {{ $order->currency }}).
                            </div>
                        </dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8"><span class="badge badge-{{ $order->status === 'paid' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($order->status) }}</span></dd>
                        @if($order->ticket)
                            <dt class="col-sm-4">Ticket</dt>
                            <dd class="col-sm-8"><code>{{ $order->ticket->ticket_code }}</code></dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @if($order->isPending())
                <div class="card card-outline card-success">
                    <div class="card-header"><h3 class="card-title mb-0">Verify payment</h3></div>
                    <div class="card-body">
                        <p class="text-muted">Confirm you received this transfer, then mark paid. The buyer gets an SMS and can open their ticket.</p>
                        <form method="POST" action="{{ route('admin.online-orders.paid', $order) }}" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label>Note (optional)</label>
                                <textarea name="admin_note" class="form-control" rows="2"></textarea>
                            </div>
                            <button class="btn btn-success btn-block">Mark as paid & issue ticket</button>
                        </form>
                        <form method="POST" action="{{ route('admin.online-orders.reject', $order) }}" onsubmit="return confirm('Reject this order?')">
                            @csrf
                            <div class="form-group">
                                <label>Rejection note</label>
                                <textarea name="admin_note" class="form-control" rows="2"></textarea>
                            </div>
                            <button class="btn btn-outline-danger btn-block">Reject</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <p class="mb-1"><strong>Reviewed</strong>: {{ $order->reviewed_at?->format('M j, Y H:i') ?: '—' }}</p>
                        <p class="mb-0 text-muted">{{ $order->admin_note ?: 'No note' }}</p>
                    </div>
                </div>
            @endif

            @unless($order->isCancelled())
                <div class="card card-outline card-danger mt-3">
                    <div class="card-header"><h3 class="card-title mb-0">Delete purchase</h3></div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Removes this ticket sale so <strong>revenue and event capacity</strong> are restored.
                            The customer’s name and phone stay in the database.
                        </p>
                        <form method="POST"
                              action="{{ route('admin.online-orders.destroy', $order) }}"
                              onsubmit="return confirm('Delete this online purchase? The ticket will be removed and capacity restored. Customer contact will be kept.');">
                            @csrf
                            @method('DELETE')
                            <div class="form-group">
                                <label>Note (optional)</label>
                                <textarea name="admin_note" class="form-control" rows="2" placeholder="Reason for deleting this purchase"></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-trash-alt mr-1"></i> Delete purchase
                            </button>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>
</x-app-layout>
