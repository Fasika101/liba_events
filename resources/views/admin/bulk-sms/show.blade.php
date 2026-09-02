<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Bulk SMS result</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.bulk-sms.index') }}">Bulk SMS</a></li>
                    <li class="breadcrumb-item active">Result</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-primary mb-3">
        <div class="card-body">
            <h5>{{ $bulkSmsLog->title ?: 'Bulk SMS' }}</h5>
            <p class="text-muted mb-2">{{ $bulkSmsLog->created_at->format('M j, Y g:i A') }} · by {{ $bulkSmsLog->sender->name ?? 'Admin' }}</p>
            <p class="mb-2"><strong>{{ $bulkSmsLog->success_count }}</strong> sent,
                <strong>{{ $bulkSmsLog->failed_count }}</strong> failed,
                <strong>{{ $bulkSmsLog->skipped_count }}</strong> skipped
                of {{ $bulkSmsLog->recipient_count }} recipients</p>
            <blockquote class="blockquote small bg-light p-3 mb-0">{{ $bulkSmsLog->message }}</blockquote>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header"><h3 class="card-title mb-0">Recipients</h3></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Phone</th><th>Name</th><th>Status</th><th>Error</th></tr></thead>
                <tbody>
                    @foreach ($bulkSmsLog->items as $item)
                        <tr>
                            <td>{{ $item->phone }}</td>
                            <td>{{ $item->recipient_name ?? '—' }}</td>
                            <td><span class="badge badge-{{ $item->status === 'sent' ? 'success' : ($item->status === 'skipped' ? 'secondary' : 'danger') }}">{{ $item->status }}</span></td>
                            <td class="small text-muted">{{ $item->error_message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('admin.bulk-sms.index') }}" class="btn btn-outline-secondary mt-3">Back to Bulk SMS</a>
</x-app-layout>
