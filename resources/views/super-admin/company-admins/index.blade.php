<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Company admins</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Company admins</li>
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
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-user-shield mr-2"></i> All organization admins
            </h3>
            <a href="{{ route('super-admin.company-admins.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Add company admin
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Admin</th>
                        <th>Email</th>
                        <th>Organization</th>
                        <th>Org status</th>
                        <th>Account</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($admins as $admin)
                        <tr>
                            <td class="font-weight-bold">{{ $admin->name }}</td>
                            <td class="text-muted">{{ $admin->email }}</td>
                            <td>
                                @if ($admin->company)
                                    <a href="{{ route('super-admin.companies.show', $admin->company) }}">
                                        {{ $admin->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($admin->company?->is_suspended)
                                    <span class="badge badge-warning">Suspended</span>
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                            <td>
                                @if ($admin->is_suspended)
                                    <span class="badge badge-secondary">User suspended</span>
                                @else
                                    <span class="badge badge-light border">OK</span>
                                @endif
                            </td>
                            <td class="text-right text-nowrap">
                                @if ($admin->company)
                                    <a href="{{ route('super-admin.companies.show', $admin->company) }}"
                                       class="btn btn-sm btn-outline-primary mr-1">
                                        Dashboard
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('super-admin.users.destroy', $admin) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Delete admin account for ' + @json($admin->name) + '? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete user">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No company admins yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="card-footer">{{ $admins->links() }}</div>
        @endif
    </div>
</x-app-layout>
