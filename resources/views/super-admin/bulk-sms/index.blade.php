<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Bulk SMS</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Bulk SMS</li>
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

    @if (! $smsConfigured)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            SMS provider is not fully configured.
            <a href="{{ route('super-admin.sms-settings.edit') }}">Configure SMS settings</a> before sending live messages.
        </div>
    @elseif ($smsDriver === 'log')
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            SMS driver is set to <strong>Log only</strong> — messages will be written to the application log instead of being sent.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="fas fa-bullhorn mr-2"></i>Send update to organizations</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.bulk-sms.send') }}"
                      onsubmit="return confirm('Send this message to the selected organizations?');">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title">Internal title (optional)</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="e.g. Holiday greeting 2026">
                            <small class="form-text text-muted">For your records only — not included in the SMS.</small>
                            @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="message">Message <span class="text-danger">*</span></label>
                            <div class="mb-2">
                                <span class="text-muted small mr-2">Quick templates:</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary mr-1 template-btn"
                                        data-template="Hello {admin_name}, we have launched a new feature on {app_name}! Log in to explore the latest updates for {organization}.">
                                    New feature
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary mr-1 template-btn"
                                        data-template="Dear {admin_name}, happy holidays from the {app_name} team! Wishing {organization} a wonderful season.">
                                    Holiday greeting
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary template-btn"
                                        data-template="Hello {admin_name}, this is a reminder from {app_name} regarding {organization}. Please sign in to your admin account for more details.">
                                    General update
                                </button>
                            </div>
                            <textarea name="message" id="message" rows="5"
                                      class="form-control @error('message') is-invalid @enderror"
                                      required maxlength="1000"
                                      placeholder="Write your message here…">{{ old('message') }}</textarea>
                            <small class="form-text text-muted">
                                Placeholders: <code>{organization}</code>, <code>{admin_name}</code>, <code>{app_name}</code>
                            </small>
                            @error('message')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0 font-weight-bold">Recipients</label>
                            <div>
                                <button type="button" id="select-all-with-phone" class="btn btn-sm btn-outline-primary mr-1">
                                    Select all with phone
                                </button>
                                <button type="button" id="clear-selection" class="btn btn-sm btn-outline-secondary">
                                    Clear
                                </button>
                            </div>
                        </div>

                        @error('company_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                        <div class="border rounded" style="max-height:320px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th style="width:40px;">
                                            <input type="checkbox" id="select-all-toggle" title="Select all">
                                        </th>
                                        <th>Organization</th>
                                        <th>Admin</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($organizations as $organization)
                                        @php
                                            $phone = $organization->smsPhone();
                                            $oldSelected = collect(old('company_ids', []))->contains($organization->id);
                                        @endphp
                                        <tr class="{{ $phone ? '' : 'text-muted bg-light' }}">
                                            <td>
                                                <input type="checkbox"
                                                       name="company_ids[]"
                                                       value="{{ $organization->id }}"
                                                       class="org-checkbox"
                                                       data-has-phone="{{ $phone ? '1' : '0' }}"
                                                       @checked($oldSelected)
                                                       @disabled(! $phone)>
                                            </td>
                                            <td class="font-weight-bold">{{ $organization->name }}</td>
                                            <td>{{ $organization->primaryAdmin?->name ?? '—' }}</td>
                                            <td>
                                                @if ($phone)
                                                    {{ $phone }}
                                                @else
                                                    <span class="text-danger small">No phone</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No organizations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Uses organization phone first, then the primary admin phone. Organizations without a phone cannot be selected.
                        </small>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Send bulk SMS
                        </button>
                        <a href="{{ route('super-admin.sms-settings.edit') }}" class="btn btn-default">
                            SMS settings
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title mb-0">Recent campaigns</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentLogs as $recent)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold">
                                            {{ $recent->title ?: 'Bulk message' }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $recent->created_at->format('M j, Y g:i A') }}
                                            · {{ $recent->success_count }}/{{ $recent->total_count }} sent
                                        </small>
                                    </div>
                                    <a href="{{ route('super-admin.bulk-sms.show', $recent) }}" class="btn btn-xs btn-outline-primary">
                                        View
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-4">No bulk messages sent yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.template-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('message').value = btn.dataset.template;
            });
        });

        const selectAllToggle = document.getElementById('select-all-toggle');
        const orgCheckboxes = () => Array.from(document.querySelectorAll('.org-checkbox:not(:disabled)'));

        if (selectAllToggle) {
            selectAllToggle.addEventListener('change', function () {
                orgCheckboxes().forEach(cb => cb.checked = selectAllToggle.checked);
            });
        }

        document.getElementById('select-all-with-phone')?.addEventListener('click', function () {
            orgCheckboxes().forEach(cb => cb.checked = true);
            if (selectAllToggle) selectAllToggle.checked = true;
        });

        document.getElementById('clear-selection')?.addEventListener('click', function () {
            orgCheckboxes().forEach(cb => cb.checked = false);
            if (selectAllToggle) selectAllToggle.checked = false;
        });
    </script>
    @endpush
</x-app-layout>
