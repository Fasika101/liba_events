<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $accounts = $company->bankAccounts()->get();

        return view('admin.bank-accounts.index', compact('company', 'accounts'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->requireCompanyId();

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_code' => ['nullable', 'string', 'max:40'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        CompanyBankAccount::create([
            ...$data,
            'company_id' => $companyId,
            'is_active' => true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Bank account added.');
    }

    public function update(Request $request, CompanyBankAccount $bankAccount)
    {
        $this->authorizeAccount($bankAccount);

        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_code' => ['nullable', 'string', 'max:40'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:80'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $bankAccount->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $bankAccount->is_active),
            'sort_order' => $data['sort_order'] ?? $bankAccount->sort_order,
        ]);

        return back()->with('status', 'Bank account updated.');
    }

    public function destroy(CompanyBankAccount $bankAccount)
    {
        $this->authorizeAccount($bankAccount);
        $bankAccount->delete();

        return back()->with('status', 'Bank account removed.');
    }

    private function authorizeAccount(CompanyBankAccount $bankAccount): void
    {
        if ((int) $bankAccount->company_id !== auth()->user()->requireCompanyId()) {
            abort(403);
        }
    }
}
