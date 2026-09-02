<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Bulk SMS result</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.bulk-sms.index') }}">Bulk SMS</a></li>
                    <li class="breadcrumb-item active">#{{ $log->id }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('status') }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Sent</span>
                    <span class="info-box-number">{{ $log->success_count }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Failed</span>
                    <span class="info-box-number">{{ $log->failed_count }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Skipped</span>
                    <span class="info-box-number">{{ $log->skipped_count }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total selected</span>
                    <span class="info-box-number">{{ $log->total_count }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $log->title ?: 'Bulk message' }}</h3>
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>Message sent:</strong></p>
            <div class="bg-light border rounded p-3 mb-3">{{ $log->message }}</div>
            <p class="text-muted small mb-0">
                Sent by {{ $log->sender?->name ?? 'Unknown' }}
                on {{ $log->created_at->format('M j, Y g:i A') }}
            </p>
        </div>
        <div class="card-footer">
            <a href="{{ route('super-admin.bulk-sms.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left mr-1"></i> Send another
            </a>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header">
            <h3 class="card-title mb-0">Delivery details</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Organization</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($log->items as $item)
                        <tr>
                            <td class="font-weight-bold">{{ $item->company_name }}</td>
                            <td>{{ $item->phone ?? '—' }}</td>
                            <td>
                                @if ($item->status === 'sent')
                                    <span class="badge badge-success">Sent</span>
                                @elseif ($item->status === 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    <span class="badge badge-warning">Skipped</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $item->error_message ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
