<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyAdminController extends Controller
{
    public function index()
    {
        $admins = User::query()
            ->where('role', 'admin')
            ->whereNotNull('company_id')
            ->with('company')
            ->orderBy('company_id')
            ->orderBy('name')
            ->paginate(25);

        return view('super-admin.company-admins.index', compact('admins'));
    }

    public function create(Request $request)
    {
        $companyId = $request->query('company_id');
        $company = $companyId ? Company::findOrFail($companyId) : null;

        $companies = Company::orderBy('name')->get();

        return view('super-admin.company-admins.create', compact('companies', 'company'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id'    => ['required', 'exists:companies,id'],
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'company_id' => (int) $data['company_id'],
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'admin',
        ]);

        return redirect()->route('super-admin.company-admins.index')
            ->with('status', 'Company admin created successfully.');
    }
}
