<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'companies' => Company::count(),
            'admins'    => User::where('role', 'admin')->count(),
            'agents'    => User::where('role', 'agent')->count(),
        ];

        $recentCompanies = Company::withCount(['users', 'events'])
            ->latest()
            ->take(8)
            ->get();

        return view('super-admin.dashboard', compact('summary', 'recentCompanies'));
    }
}
