<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Add company admin</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.companies.index') }}">Companies</a></li>
                    <li class="breadcrumb-item active">Add admin</li>
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
                    <h3 class="card-title">New admin user</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.company-admins.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="company_id">Company <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach ($companies as $c)
                                    <option value="{{ $c->id }}" @selected(old('company_id', $company?->id) == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror" required maxlength="255">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror" required maxlength="255">
                            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Confirm password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-1"></i> Create admin
                        </button>
                        <a href="{{ route('super-admin.companies.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
