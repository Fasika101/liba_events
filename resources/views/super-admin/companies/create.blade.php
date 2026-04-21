<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">New company</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.companies.index') }}">Companies</a></li>
                    <li class="breadcrumb-item active">Create</li>
                </ol>
            </div>
        </div>
    </x-slot>

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
        <div class="col-lg-8 col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Company details</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.companies.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Company name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror" required maxlength="255">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <hr class="my-4">
                        <p class="text-muted mb-3">Optional: create the first company admin now. You can add more later.</p>

                        <div class="form-group">
                            <label for="admin_name">Admin name</label>
                            <input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}"
                                   class="form-control @error('admin_name') is-invalid @enderror" maxlength="255">
                            @error('admin_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="admin_email">Admin email</label>
                            <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}"
                                   class="form-control @error('admin_email') is-invalid @enderror" maxlength="255">
                            @error('admin_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Admin password</label>
                            <input type="password" name="admin_password" id="admin_password"
                                   class="form-control @error('admin_password') is-invalid @enderror" autocomplete="new-password">
                            @error('admin_password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="admin_password_confirmation">Confirm password</label>
                            <input type="password" name="admin_password_confirmation" id="admin_password_confirmation"
                                   class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Create company
                        </button>
                        <a href="{{ route('super-admin.companies.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
