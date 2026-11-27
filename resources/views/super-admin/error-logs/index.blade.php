<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Error logs</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Error logs</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'sms' ? 'active' : '' }}"
               href="{{ route('super-admin.error-logs.index', ['tab' => 'sms']) }}">
                SMS errors
                @if($smsFailedCount > 0)
                    <span class="badge badge-danger">{{ $smsFailedCount }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'verify-et' ? 'active' : '' }}"
               href="{{ route('super-admin.error-logs.index', ['tab' => 'verify-et']) }}">
                Verify.et errors
                @if($verifyFailedCount > 0)
                    <span class="badge badge-danger">{{ $verifyFailedCount }}</span>
                @endif
            </a>
        </li>
    </ul>

    @if($tab === 'sms')
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-sms mr-2"></i>Failed ticket / custom SMS</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>When</th>
                            <th>Organization</th>
                            <th>Phone</th>
                            <th>Purpose</th>
                            <th>Error</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($smsLogs as $log)
                            <tr>
                                <td class="text-nowrap small">{{ $log->created_at?->format('M j, Y H:i') }}</td>
                                <td>{{ $log->company?->name ?: '—' }}</td>
                                <td><code>{{ $log->phone }}</code></td>
                                <td><span class="badge badge-secondary">{{ $log->purpose }}</span></td>
                                <td class="text-danger small" style="max-width:280px;">
                                    {{ $log->error_message ?: 'Failed (no details)' }}
                                </td>
                                <td class="small text-muted" style="max-width:260px;">
                                    {{ \Illuminate\Support\Str::limit($log->message, 80) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No failed SMS logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($smsLogs, 'hasPages') && $smsLogs->hasPages())
                <div class="card-footer">{{ $smsLogs->links() }}</div>
            @endif
        </div>

        @if($bulkSmsFails->isNotEmpty())
            <div class="card card-outline card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fas fa-bullhorn mr-2"></i>Bulk SMS with failures</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>When</th>
                                <th>Title</th>
                                <th>Sent by</th>
                                <th>Failed</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bulkSmsFails as $bulk)
                                <tr>
                                    <td class="small">{{ $bulk->created_at?->format('M j, Y H:i') }}</td>
                                    <td>{{ $bulk->title ?: '—' }}</td>
                                    <td>{{ $bulk->sender?->name ?: '—' }}</td>
                                    <td>
                                        <span class="badge badge-danger">{{ $bulk->failed_count }}</span>
                                        / {{ $bulk->total_count }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('super-admin.bulk-sms.show', $bulk) }}" class="btn btn-xs btn-outline-primary">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-university mr-2"></i>Verify.et failures & mismatches</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>When</th>
                            <th>Order</th>
                            <th>Organization</th>
                            <th>Event</th>
                            <th>Buyer</th>
                            <th>Txn</th>
                            <th>Status note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($verifyOrders as $order)
                            <tr>
                                <td class="text-nowrap small">{{ $order->updated_at?->format('M j, Y H:i') }}</td>
                                <td>
                                    <code>{{ $order->public_code }}</code>
                                    <div class="small text-muted">{{ ucfirst($order->status) }}</div>
                                </td>
                                <td>{{ $order->company?->name ?: '—' }}</td>
                                <td>{{ $order->event?->title ?: '—' }}</td>
                                <td>
                                    <div>{{ $order->buyer_name }}</div>
                                    <div class="small text-muted">{{ $order->buyer_phone }}</div>
                                </td>
                                <td><code class="small">{{ \Illuminate\Support\Str::limit($order->transaction_reference, 24) ?: '—' }}</code></td>
                                <td class="text-danger small" style="max-width:320px;">
                                    {{ $order->verify_et_status }}
                                    @if($order->verify_et_request_id)
                                        <div class="text-muted mt-1">Req: {{ $order->verify_et_request_id }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No Verify.et error records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($verifyOrders, 'hasPages') && $verifyOrders->hasPages())
                <div class="card-footer">{{ $verifyOrders->links() }}</div>
            @endif
        </div>
    @endif
</x-app-layout>
