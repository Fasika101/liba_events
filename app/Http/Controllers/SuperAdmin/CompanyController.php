<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Event;
use App\Models\SmsPackage;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SmsAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['users', 'events'])
            ->latest()
            ->paginate(15);

        return view('super-admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('super-admin.companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ]);

        $admin = null;
        if ($request->filled('admin_email')) {
            $admin = $request->validate([
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $company = Company::create([
            'name' => $data['name'],
        ]);

        if ($admin !== null) {
            User::create([
                'company_id' => $company->id,
                'name' => $admin['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($admin['admin_password']),
                'role' => 'admin',
            ]);
        }

        return redirect()->route('super-admin.companies.index')
            ->with('status', 'Company created successfully.');
    }

    public function show(Company $company)
    {
        $eventsCount = $company->events()->count();

        $ticketAgg = Ticket::query()
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $company->id)
            ->selectRaw('COUNT(tickets.id) as tickets_sold, COALESCE(SUM(tickets.price_paid), 0) as revenue')
            ->first();

        $ticketsSold = (int) ($ticketAgg->tickets_sold ?? 0);
        $revenue = (float) ($ticketAgg->revenue ?? 0);

        $agents = User::query()
            ->where('company_id', $company->id)
            ->where('role', 'agent')
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->orderBy('name')
            ->get();

        $admins = User::query()
            ->where('company_id', $company->id)
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();

        $adminsCount = $admins->count();

        $recentEvents = $company->events()
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->latest('start_at')
            ->take(6)
            ->get();

        $smsPackages = SmsPackage::query()->where('is_active', true)->orderBy('sort_order')->get();

        $smsStats = app(SmsAccountingService::class)->companySummary($company);

        return view('super-admin.companies.show', compact(
            'company',
            'eventsCount',
            'ticketsSold',
            'revenue',
            'agents',
            'admins',
            'adminsCount',
            'recentEvents',
            'smsPackages',
            'smsStats'
        ));
    }

    public function toggleSuspension(Company $company)
    {
        $company->update(['is_suspended' => ! $company->is_suspended]);
        $company->refresh();

        $message = $company->is_suspended
            ? 'Organization suspended. Admins and agents for this organization can no longer sign in.'
            : 'Organization reactivated. Admins and agents can sign in again.';

        return back()->with('status', $message);
    }

    public function edit(Company $company)
    {
        return view('super-admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $company->update($data);

        return redirect()->route('super-admin.companies.show', $company)
            ->with('status', 'Organization updated successfully.');
    }

    public function destroy(Company $company)
    {
        DB::transaction(function () use ($company) {
            Event::withTrashed()->where('company_id', $company->id)->forceDelete();
            User::query()->where('company_id', $company->id)->delete();
            $company->delete();
        });

        return redirect()->route('super-admin.companies.index')
            ->with('status', 'Organization and all related data were removed.');
    }
}
