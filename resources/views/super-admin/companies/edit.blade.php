<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit organization</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.companies.index') }}">Companies</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.companies.show', $company) }}">{{ $company->name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
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
                    <h3 class="card-title">Organization name</h3>
                </div>
                <form method="POST" action="{{ route('super-admin.companies.update', $company) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required maxlength="255">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save changes
                        </button>
                        <a href="{{ route('super-admin.companies.show', $company) }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
