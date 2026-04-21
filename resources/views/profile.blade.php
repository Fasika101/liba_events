<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4 class="mb-0">Profile Settings</h4>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('avatar_status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-1"></i> {{ session('avatar_status') }}
        </div>
    @endif

    <div class="row">
        {{-- Profile Photo Card --}}
        <div class="col-12 mb-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-camera mr-2"></i> Profile Photo
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center flex-wrap" style="gap:24px;">
                        {{-- Current avatar --}}
                        <div id="avatarPreviewWrap"
                             style="width:100px;height:100px;border-radius:50%;overflow:hidden;
                                    border:3px solid var(--accent);flex-shrink:0;
                                    display:flex;align-items:center;justify-content:center;
                                    background:linear-gradient(135deg,var(--accent),#7c5cbf);">
                            @if(auth()->user()->avatarUrl())
                                <img id="avatarPreview" src="{{ auth()->user()->avatarUrl() }}"
                                     alt="{{ auth()->user()->name }}"
                                     style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <img id="avatarPreview" src="" style="display:none;width:100%;height:100%;object-fit:cover;">
                                <span id="avatarInitial"
                                      style="font-size:2.2rem;font-weight:700;color:#fff;line-height:1;">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <p class="text-muted mb-2" style="font-size:.85rem;">
                                Upload a photo to personalize your profile. It will appear in the sidebar and top bar.
                            </p>
                            <form method="POST" action="{{ route('profile.avatar') }}"
                                  enctype="multipart/form-data" class="d-inline-block">
                                @csrf
                                <label class="btn btn-primary btn-sm mb-2" style="cursor:pointer;">
                                    <i class="fas fa-upload mr-1"></i> Choose Photo
                                    <input type="file" name="avatar" accept="image/*" class="d-none"
                                           onchange="previewProfileAvatar(this)">
                                </label>
                                <button type="submit" id="saveAvatarBtn" class="btn btn-success btn-sm mb-2" style="display:none;">
                                    <i class="fas fa-save mr-1"></i> Save Photo
                                </button>
                            </form>

                            @if(auth()->user()->avatarUrl())
                                <form method="POST" action="{{ route('profile.avatar.remove') }}"
                                      class="d-inline-block"
                                      onsubmit="return confirm('Remove your profile photo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm mb-2">
                                        <i class="fas fa-trash mr-1"></i> Remove Photo
                                    </button>
                                </form>
                            @endif

                            @error('avatar')
                                <div class="text-danger small mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div class="text-muted small mt-1">Allowed: JPG, PNG, WebP · Max 2 MB</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Profile Information --}}
        <div class="col-lg-6 col-12 mb-4">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-user mr-2"></i> Profile Information
                    </h3>
                </div>
                <div class="card-body">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>
        </div>

        {{-- Update Password --}}
        <div class="col-lg-6 col-12 mb-4">
            <div class="card card-outline card-warning h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-key mr-2"></i> Update Password
                    </h3>
                </div>
                <div class="card-body">
                    <livewire:profile.update-password-form />
                </div>
            </div>
        </div>

        {{-- Delete Account --}}
        <div class="col-12 mb-4">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Danger Zone
                    </h3>
                </div>
                <div class="card-body">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function previewProfileAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const preview = document.getElementById('avatarPreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
                const initial = document.getElementById('avatarInitial');
                if (initial) initial.style.display = 'none';
                document.getElementById('saveAvatarBtn').style.display = 'inline-block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
    @endpush
</x-app-layout>
