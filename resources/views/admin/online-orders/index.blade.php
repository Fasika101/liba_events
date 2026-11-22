<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Online orders
                    @if($pendingCount > 0)
                        <span class="badge badge-warning">{{ $pendingCount }} pending</span>
                    @endif
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Online Orders</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <label class="mr-2">Status</label>
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="all" @selected(($status ?: 'all') === 'all')>All</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="paid" @selected($status === 'paid')>Paid</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Event</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Txn ID</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><code>{{ $order->public_code }}</code></td>
                                <td>{{ $order->event?->title }}</td>
                                <td>
                                    <div>{{ $order->buyer_name }}</div>
                                    <div class="text-muted small">{{ $order->buyer_phone }}</div>
                                </td>
                                <td>{{ number_format($order->amount, 2) }} {{ $order->currency }}</td>
                                <td><code>{{ $order->transaction_reference ?: '—' }}</code></td>
                                <td>
                                    @if($order->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @elseif($order->status === 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.online-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No online orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</x-app-layout>
