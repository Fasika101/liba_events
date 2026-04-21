<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Companies</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Companies</li>
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

    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">All organizations</h3>
            <div>
                <a href="{{ route('super-admin.company-admins.create') }}" class="btn btn-outline-primary btn-sm mr-2">
                    <i class="fas fa-user-plus mr-1"></i> Add company admin
                </a>
                <a href="{{ route('super-admin.companies.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> New company
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Users</th>
                        <th>Events</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td class="font-weight-bold">
                                <a href="{{ route('super-admin.companies.show', $company) }}" class="text-dark">
                                    {{ $company->name }}
                                    <i class="fas fa-chevron-right ml-1 text-muted" style="font-size:.65rem;"></i>
                                </a>
                                @if ($company->is_suspended)
                                    <span class="badge badge-warning ml-1">Suspended</span>
                                @endif
                            </td>
                            <td>{{ $company->users_count }}</td>
                            <td>{{ $company->events_count }}</td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('super-admin.companies.show', $company) }}"
                                   class="btn btn-sm btn-outline-secondary mr-1" title="Dashboard">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="{{ route('super-admin.companies.edit', $company) }}"
                                   class="btn btn-sm btn-outline-primary mr-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('super-admin.company-admins.create', ['company_id' => $company->id]) }}"
                                   class="btn btn-sm btn-outline-primary mr-1">
                                    Add admin
                                </a>
                                <form method="POST" action="{{ route('super-admin.companies.destroy', $company) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete ' + @json($company->name) + '? All events, tickets, and user accounts for this organization will be permanently removed.');">
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
                            <td colspan="4" class="text-center text-muted py-4">No companies yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($companies->hasPages())
            <div class="card-footer">{{ $companies->links() }}</div>
        @endif
    </div>
</x-app-layout>
