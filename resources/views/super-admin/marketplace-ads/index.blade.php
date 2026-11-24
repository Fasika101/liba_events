<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Ad placements</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Ad placements</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>{{ session('status') }}
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Marketplace ads</h3>
            <a href="{{ route('super-admin.marketplace-ads.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> New ad
            </a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Ad</th>
                        <th>Placement</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ads as $ad)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($ad->imageUrl())
                                        <img src="{{ $ad->imageUrl() }}" alt="" class="mr-2 rounded" style="width:48px;height:36px;object-fit:cover;">
                                    @endif
                                    <div>
                                        <div class="font-weight-bold">{{ $ad->title }}</div>
                                        @if($ad->body)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($ad->body, 60) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $ad->placementLabel() }}</td>
                            <td class="small text-muted">
                                @if($ad->starts_at || $ad->ends_at)
                                    {{ $ad->starts_at?->format('M j') ?? '—' }} → {{ $ad->ends_at?->format('M j') ?? '—' }}
                                @else
                                    Always
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $ad->is_active ? 'success' : 'secondary' }}">
                                    {{ $ad->is_active ? 'Active' : 'Off' }}
                                </span>
                            </td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('super-admin.marketplace-ads.edit', $ad) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('super-admin.marketplace-ads.destroy', $ad) }}" class="d-inline" onsubmit="return confirm('Delete this ad?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No ads yet. Create one to show on Discover / Organizers / footer.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
