<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->requireCompanyId();

        $events = Event::forCompany($companyId)
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->latest('start_at')
            ->take(6)
            ->get();

        $agentStats = User::where('role', 'agent')
            ->where('company_id', $companyId)
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->orderByDesc('tickets_count')
            ->take(10)
            ->get();

        $ticketQuery = Ticket::whereHas('event', fn ($q) => $q->where('company_id', $companyId));

        $summary = [
            'events'  => Event::forCompany($companyId)->count(),
            'tickets' => (clone $ticketQuery)->count(),
            'revenue' => (clone $ticketQuery)->sum('price_paid'),
        ];

        return view('admin.dashboard', compact('events', 'agentStats', 'summary'));
    }
}
