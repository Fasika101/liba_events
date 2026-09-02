<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Edit organization contacts</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.registration-requests.index') }}">Registrations</a></li>
                    <li class="breadcrumb-item active">{{ $company->name }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
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
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $company->name }}</h3>
                </div>
                @if ($admin)
                    <form method="POST" action="{{ route('super-admin.registration-requests.organizations.update', $company) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <p class="text-muted small">
                                Primary admin: <strong>{{ $admin->name }}</strong>.
                                Update login email and phone numbers for this organization.
                            </p>

                            <div class="form-group">
                                <label for="admin_email">Admin login email <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" id="admin_email"
                                       value="{{ old('admin_email', $admin->email) }}"
                                       class="form-control @error('admin_email') is-invalid @enderror" required>
                                @error('admin_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label for="admin_phone_digits">Admin phone</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+251</span>
                                    </div>
                                    <input type="tel" name="admin_phone_digits" id="admin_phone_digits"
                                           inputmode="numeric" maxlength="9"
                                           value="{{ old('admin_phone_digits', $admin->phone ? ltrim(str_replace('+251', '', $admin->phone), '') : '') }}"
                                           class="form-control @error('admin_phone_digits') is-invalid @enderror"
                                           placeholder="912345678">
                                </div>
                                <small class="form-text text-muted">Applicant / admin contact number (9 digits after +251)</small>
                                @error('admin_phone_digits')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="organization_phone_digits">Organization phone (SMS)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">+251</span>
                                    </div>
                                    <input type="tel" name="organization_phone_digits" id="organization_phone_digits"
                                           inputmode="numeric" maxlength="9"
                                           value="{{ old('organization_phone_digits', $company->phone ? ltrim(str_replace('+251', '', $company->phone), '') : '') }}"
                                           class="form-control @error('organization_phone_digits') is-invalid @enderror"
                                           placeholder="912345678">
                                </div>
                                <small class="form-text text-muted">Organization SMS / verification number</small>
                                @error('organization_phone_digits')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save changes
                            </button>
                            <a href="{{ route('super-admin.registration-requests.index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                @else
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            This organization has no admin user yet.
                            <a href="{{ route('super-admin.company-admins.create', ['company_id' => $company->id]) }}">Add a company admin</a> first.
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('super-admin.registration-requests.index') }}" class="btn btn-default">Back</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
