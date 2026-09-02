<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Registration request</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.registration-requests.index') }}">Registrations</a></li>
                    <li class="breadcrumb-item active">#{{ $request->id }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('status') }}
        </div>
    @endif

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
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $request->organization_name }}</h3>
                    @if ($request->isPendingVerification())
                        <span class="badge badge-info">Awaiting SMS verification</span>
                    @elseif ($request->isPending())
                        <span class="badge badge-warning">Pending approval</span>
                    @elseif ($request->isApproved())
                        <span class="badge badge-success">Approved</span>
                    @else
                        <span class="badge badge-secondary">Rejected</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($request->sms_verification_code && $request->isAwaitingReview())
                        <div class="alert alert-info">
                            <strong><i class="fas fa-sms mr-1"></i> SMS verification code:</strong>
                            <code class="ml-2 font-weight-bold" style="font-size:1.1rem;letter-spacing:.2em;">{{ $request->sms_verification_code }}</code>
                            @if ($request->isPendingVerification())
                                <div class="small mt-1 text-muted">Share this code if the applicant did not receive the SMS.</div>
                            @endif
                        </div>
                    @endif

                    <h5 class="text-muted text-uppercase small font-weight-bold mb-3">Applicant</h5>
                    <dl class="row mb-4">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $request->fullName() }}</dd>
                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $request->address }}</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $request->phone }}</dd>
                    </dl>

                    <h5 class="text-muted text-uppercase small font-weight-bold mb-3">Admin login</h5>
                    <dl class="row mb-4">
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $request->admin_email ?? '—' }}</dd>
                        <dt class="col-sm-4">Password</dt>
                        <dd class="col-sm-8"><span class="text-muted">Set by applicant (stored securely)</span></dd>
                    </dl>

                    <h5 class="text-muted text-uppercase small font-weight-bold mb-3">Organization</h5>
                    <dl class="row mb-4">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $request->organization_name }}</dd>
                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $request->organization_address }}</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">
                            {{ $request->organization_phone }}
                            @if ($request->phone_verified_at)
                                <span class="badge badge-success ml-1">SMS verified</span>
                            @endif
                        </dd>
                    </dl>

                    <h5 class="text-muted text-uppercase small font-weight-bold mb-3">Submission</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Submitted</dt>
                        <dd class="col-sm-8">{{ $request->created_at->format('M j, Y g:i A') }}</dd>
                        @if ($request->reviewed_at)
                            <dt class="col-sm-4">Reviewed</dt>
                            <dd class="col-sm-8">
                                {{ $request->reviewed_at->format('M j, Y g:i A') }}
                                @if ($request->reviewer)
                                    by {{ $request->reviewer->name }}
                                @endif
                            </dd>
                        @endif
                        @if ($request->rejection_reason)
                            <dt class="col-sm-4">Rejection reason</dt>
                            <dd class="col-sm-8">{{ $request->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('super-admin.registration-requests.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Back to list
                    </a>
                    <form method="POST" action="{{ route('super-admin.registration-requests.destroy', $request) }}"
                          class="d-inline"
                          onsubmit="return confirm('Delete this registration request permanently?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash-alt mr-1"></i> Delete request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if ($request->isAwaitingReview())
            <div class="col-lg-5">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Edit application contacts</h3>
                    </div>
                    <form method="POST" action="{{ route('super-admin.registration-requests.update', $request) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="admin_email">Admin login email</label>
                                <input type="email" name="admin_email" id="admin_email"
                                       value="{{ old('admin_email', $request->admin_email) }}"
                                       class="form-control @error('admin_email') is-invalid @enderror" required>
                                @error('admin_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="phone_digits">Applicant phone</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">+251</span></div>
                                    <input type="tel" name="phone_digits" id="phone_digits" maxlength="9"
                                           value="{{ old('phone_digits', ltrim(str_replace('+251', '', $request->phone), '')) }}"
                                           class="form-control @error('phone_digits') is-invalid @enderror" required>
                                </div>
                                @error('phone_digits')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group mb-0">
                                <label for="organization_phone_digits">Organization phone (SMS)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">+251</span></div>
                                    <input type="tel" name="organization_phone_digits" id="organization_phone_digits" maxlength="9"
                                           value="{{ old('organization_phone_digits', ltrim(str_replace('+251', '', $request->organization_phone), '')) }}"
                                           class="form-control @error('organization_phone_digits') is-invalid @enderror" required>
                                </div>
                                @error('organization_phone_digits')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save changes
                            </button>
                        </div>
                    </form>
                </div>

                @if ($request->isPending())
                <div class="card card-outline card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Approve registration</h3>
                    </div>
                    <form method="POST" action="{{ route('super-admin.registration-requests.approve', $request) }}"
                          onsubmit="return confirm('Approve this registration and create the organization account?');">
                        @csrf
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                Approving will create the organization and admin account for
                                <strong>{{ $request->fullName() }}</strong> using login email
                                <strong>{{ $request->admin_email }}</strong>.
                            </p>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check mr-1"></i> Approve registration
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <div class="card card-outline card-danger mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Reject</h3>
                    </div>
                    <form method="POST" action="{{ route('super-admin.registration-requests.reject', $request) }}"
                          onsubmit="return confirm('Reject this registration request?');">
                        @csrf
                        <div class="card-body">
                            @if ($request->isPendingVerification())
                                <p class="text-muted small">The applicant has not completed SMS verification yet.</p>
                            @endif
                            <div class="form-group mb-0">
                                <label for="rejection_reason">Reason (optional)</label>
                                <textarea name="rejection_reason" id="rejection_reason" rows="3"
                                          class="form-control" placeholder="Optional note for internal records">{{ old('rejection_reason') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif ($request->isApproved() && $request->company_id)
            <div class="col-lg-5">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Organization account</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">This application was approved. Edit the live organization admin email and phone numbers.</p>
                        <a href="{{ route('super-admin.registration-requests.organizations.edit', $request->company_id) }}"
                           class="btn btn-primary">
                            <i class="fas fa-edit mr-1"></i> Edit organization contacts
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
