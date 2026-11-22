<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Payment accounts</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Accounts</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @unless($company->isPremium())
        <div class="alert alert-warning">
            Public ticket sales and the discovery Mini App are available on the <strong>Premium</strong> plan.
            You can still save accounts now; they appear to buyers once you are subscribed.
        </div>
    @endunless

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title mb-0">Add account</h3></div>
                <form method="POST" action="{{ route('admin.accounts.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Bank / wallet name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="e.g. Commercial Bank of Ethiopia" required>
                        </div>
                        <div class="form-group">
                            <label>Bank code (for Verify.et)</label>
                            <select name="bank_code" class="form-control">
                                <option value="">— optional —</option>
                                @foreach (['cbe'=>'CBE','telebirr'=>'Telebirr','cbebirr'=>'CBE Birr','boa'=>'Bank of Abyssinia','mpesa'=>'M-Pesa','dashen'=>'Dashen','awash'=>'Awash','coopayebirr'=>'Coopay Ebirr'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account holder name</label>
                            <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Account / phone number</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label>Instructions (optional)</label>
                            <textarea name="instructions" class="form-control" rows="2" placeholder="e.g. Use event title as remark">{{ old('instructions') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary">Save account</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Your accounts</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Bank</th>
                                    <th>Account</th>
                                    <th>Active</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($accounts as $account)
                                    <tr>
                                        <td>
                                            <strong>{{ $account->bank_name }}</strong>
                                            @if($account->bank_code)
                                                <div class="text-muted small">{{ $account->bank_code }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $account->account_name }}</div>
                                            <code>{{ $account->account_number }}</code>
                                            @if($account->instructions)
                                                <div class="text-muted small mt-1">{{ $account->instructions }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.accounts.update', $account) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="bank_name" value="{{ $account->bank_name }}">
                                                <input type="hidden" name="bank_code" value="{{ $account->bank_code }}">
                                                <input type="hidden" name="account_name" value="{{ $account->account_name }}">
                                                <input type="hidden" name="account_number" value="{{ $account->account_number }}">
                                                <input type="hidden" name="instructions" value="{{ $account->instructions }}">
                                                <input type="hidden" name="is_active" value="{{ $account->is_active ? 0 : 1 }}">
                                                <button class="btn btn-sm {{ $account->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                    {{ $account->is_active ? 'On' : 'Off' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" onsubmit="return confirm('Remove this account?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No accounts yet. Add your first payment account.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
