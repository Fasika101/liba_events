<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Registrations & organizations</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Registrations</li>
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

    @if ($pendingCount > 0)
        <div class="alert alert-warning">
            <i class="fas fa-clock mr-1"></i>
            {{ $pendingCount }} registration {{ $pendingCount === 1 ? 'application' : 'applications' }} awaiting approval.
        </div>
    @endif

    @if ($awaitingSmsCount > 0)
        <div class="alert alert-info">
            <i class="fas fa-sms mr-1"></i>
            {{ $awaitingSmsCount }} {{ $awaitingSmsCount === 1 ? 'applicant is' : 'applicants are' }} verifying their phone — SMS codes are shown below.
        </div>
    @endif

    <div class="card card-outline card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Registration applications</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Admin email</th>
                        <th>Organization</th>
                        <th>SMS code</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>
                                <span class="font-weight-bold">{{ $item->fullName() }}</span><br>
                                <small class="text-muted">{{ $item->phone }}</small>
                            </td>
                            <td>{{ $item->admin_email ?? '—' }}</td>
                            <td>
                                {{ $item->organization_name }}<br>
                                <small class="text-muted">{{ $item->organization_phone }}</small>
                            </td>
                            <td>
                                @if ($item->sms_verification_code && $item->isAwaitingReview())
                                    <code class="text-dark font-weight-bold" style="font-size:1rem;letter-spacing:.15em;">{{ $item->sms_verification_code }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->isPendingVerification())
                                    <span class="badge badge-info">Awaiting SMS</span>
                                @elseif ($item->isPending())
                                    <span class="badge badge-warning">Pending approval</span>
                                @elseif ($item->isApproved())
                                    <span class="badge badge-success">Approved</span>
                                @else
                                    <span class="badge badge-secondary">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $item->created_at->format('M j, Y g:i A') }}</td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('super-admin.registration-requests.show', $item) }}"
                                   class="btn btn-sm btn-outline-primary mr-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <form method="POST" action="{{ route('super-admin.registration-requests.destroy', $item) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete this registration request for ' + @json($item->organization_name) + '?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No registration applications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="card-footer">{{ $requests->withQueryString()->links() }}</div>
        @endif
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">All organizations</h3>
            @if ($manualOrgCount > 0)
                <span class="badge badge-light border">{{ $manualOrgCount }} set up manually (before self-registration)</span>
            @endif
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Organization</th>
                        <th>Admin</th>
                        <th>Admin email</th>
                        <th>Admin phone</th>
                        <th>Org. phone</th>
                        <th>Source</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $organization)
                        @php($admin = $organization->primaryAdmin)
                        <tr>
                            <td class="font-weight-bold">
                                {{ $organization->name }}
                                @if ($organization->is_suspended)
                                    <span class="badge badge-warning ml-1">Suspended</span>
                                @endif
                            </td>
                            <td>{{ $admin?->name ?? '—' }}</td>
                            <td>{{ $admin?->email ?? '—' }}</td>
                            <td>{{ $admin?->phone ?? '—' }}</td>
                            <td>{{ $organization->phone ?? '—' }}</td>
                            <td>
                                @if ($organization->approvedRegistrationRequest)
                                    <span class="badge badge-success">Self-registration</span>
                                @else
                                    <span class="badge badge-secondary">Manual setup</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('super-admin.registration-requests.organizations.edit', $organization) }}"
                                   class="btn btn-sm btn-outline-primary mr-1">
                                    <i class="fas fa-edit"></i> Edit contacts
                                </a>
                                <a href="{{ route('super-admin.companies.show', $organization) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-building"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No organizations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($organizations->hasPages())
            <div class="card-footer">{{ $organizations->withQueryString()->links() }}</div>
        @endif
    </div>
</x-app-layout>
