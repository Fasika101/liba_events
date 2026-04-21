<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Sales Agents</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Agents</li>
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
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Add Agent Form --}}
        <div class="col-lg-5 col-12 mb-4">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus mr-2"></i> Add New Agent
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.agents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Avatar preview --}}
                        <div class="form-group text-center">
                            <div id="avatarPreviewWrap" class="mx-auto mb-2"
                                 style="width:80px;height:80px;border-radius:50%;overflow:hidden;border:3px solid var(--accent);display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--accent),#7c5cbf);">
                                <img id="avatarPreview" src="" alt=""
                                     style="width:100%;height:100%;object-fit:cover;display:none;">
                                <i id="avatarIcon" class="fas fa-user fa-2x text-white"></i>
                            </div>
                            <label class="btn btn-sm btn-outline-primary mt-1" style="cursor:pointer;">
                                <i class="fas fa-camera mr-1"></i> Upload Photo
                                <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none"
                                       onchange="previewAvatar(this)">
                            </label>
                            <div class="text-muted small mt-1">Optional · JPG, PNG, WebP · max 2 MB</div>
                            @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   required class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Agent name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   required class="form-control @error('email') is-invalid @enderror"
                                   placeholder="agent@example.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Password</label>
                            <input type="password" name="password" required
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Confirm Password</label>
                            <input type="password" name="password_confirmation" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus mr-1"></i> Create Agent
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Agent List --}}
        <div class="col-lg-7 col-12 mb-4">
            <div class="card card-outline card-success">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-users mr-2"></i> Agent List
                        <span class="badge badge-success ml-2">{{ $agents->total() }}</span>
                    </h3>
                    <form method="POST" action="{{ route('admin.agents.telegram-menu') }}" class="d-inline mt-2 mt-md-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-info w-100 w-md-auto" title="Set bot menu so agents can open app from Telegram">
                            <i class="fab fa-telegram mr-1"></i> Set "Open App" in Bot
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    {{-- Desktop: table --}}
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:42%;">Agent</th>
                                    <th class="text-center">Tickets</th>
                                    <th class="text-right">Revenue</th>
                                    <th class="text-center" style="width:160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agents as $agent)
                                    <tr class="{{ $agent->is_suspended ? 'table-danger' : '' }}" style="opacity:{{ $agent->is_suspended ? '.75' : '1' }};">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($agent->avatarUrl())
                                                    <img src="{{ $agent->avatarUrl() }}" alt="{{ $agent->name }}"
                                                         class="rounded-circle mr-2"
                                                         style="width:36px;height:36px;object-fit:cover;border:2px solid {{ $agent->is_suspended ? '#ef4444' : 'var(--accent)' }};">
                                                @else
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white mr-2 flex-shrink-0"
                                                          style="width:36px;height:36px;font-size:.8rem;font-weight:700;
                                                                 background:{{ $agent->is_suspended ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,var(--accent),#7c5cbf)' }};">
                                                        {{ strtoupper(substr($agent->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                                <div>
                                                    <div class="font-weight-bold" style="font-size:.88rem;">
                                                        {{ $agent->name }}
                                                        @if($agent->is_suspended)
                                                            <span class="badge badge-danger ml-1" style="font-size:.65rem;">Suspended</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted" style="font-size:.75rem;">{{ $agent->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-info">{{ $agent->tickets_count }}</span>
                                        </td>
                                        <td class="text-right align-middle text-success font-weight-bold">
                                            {{ number_format($agent->tickets_sum_price_paid ?? 0, 2) }}
                                        </td>
                                        <td class="text-center align-middle">
                                            @include('admin.agents.partials.agent-actions', ['agent' => $agent])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                            <strong>No agents yet.</strong><br>
                                            <small class="text-muted">Use the form on the left to create your first agent.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: card list --}}
                    <div class="d-md-none">
                        @forelse ($agents as $agent)
                            <div class="agent-card-mobile border-bottom p-3 {{ $agent->is_suspended ? 'bg-light' : '' }}"
                                 style="opacity:{{ $agent->is_suspended ? '.85' : '1' }};">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center flex-grow-1 min-w-0 mb-2 mb-sm-0">
                                        @if($agent->avatarUrl())
                                            <img src="{{ $agent->avatarUrl() }}" alt="{{ $agent->name }}"
                                                 class="rounded-circle mr-2 flex-shrink-0"
                                                 style="width:44px;height:44px;object-fit:cover;border:2px solid {{ $agent->is_suspended ? '#ef4444' : 'var(--accent)' }};">
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white mr-2 flex-shrink-0"
                                                  style="width:44px;height:44px;font-size:.9rem;font-weight:700;
                                                         background:{{ $agent->is_suspended ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,var(--accent),#7c5cbf)' }};">
                                                {{ strtoupper(substr($agent->name, 0, 1)) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-weight-bold text-truncate">
                                                {{ $agent->name }}
                                                @if($agent->is_suspended)
                                                    <span class="badge badge-danger ml-1" style="font-size:.65rem;">Suspended</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small text-truncate">{{ $agent->email }}</div>
                                            <div class="mt-1">
                                                <span class="badge badge-info">{{ $agent->tickets_count }} tickets</span>
                                                <span class="text-success font-weight-bold ml-1">{{ number_format($agent->tickets_sum_price_paid ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap">
                                        @include('admin.agents.partials.agent-actions', ['agent' => $agent])
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 px-3">
                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                <strong>No agents yet.</strong><br>
                                <small class="text-muted">Use the form above to create your first agent.</small>
                            </div>
                        @endforelse
                    </div>
                </div>
                @if ($agents->hasPages())
                    <div class="card-footer clearfix">
                        {{ $agents->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    /* Agents page mobile responsiveness */
    @media (max-width: 767.98px) {
        .agent-card-mobile .btn { min-width: 72px !important; }
    }
    </style>
    @endpush

    @push('scripts')
    <script>
    function previewAvatar(input) {
        const preview = document.getElementById('avatarPreview');
        const icon    = document.getElementById('avatarIcon');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
                icon.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    </script>
    @endpush
</x-app-layout>
