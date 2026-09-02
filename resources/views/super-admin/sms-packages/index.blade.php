<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">SMS packages</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">SMS packages</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('status') }}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Premium SMS packages</h3>
            <a href="{{ route('super-admin.sms-packages.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New package</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>SMS count</th>
                        <th>Price (ETB)</th>
                        <th>Per SMS</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="font-weight-bold">{{ $package->name }}</td>
                            <td>{{ number_format($package->sms_count) }}</td>
                            <td>{{ number_format($package->price_etb, 2) }}</td>
                            <td>{{ number_format($package->price_per_sms, 4) }} ETB</td>
                            <td><span class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">{{ $package->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('super-admin.sms-packages.edit', $package) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('super-admin.sms-packages.destroy', $package) }}" class="d-inline" onsubmit="return confirm('Delete this package?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No packages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
