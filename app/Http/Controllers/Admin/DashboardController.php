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

        $ticketQuery = Ticket::whereHas('event', fn ($q) => $q->withTrashed()->where('company_id', $companyId));

        $miniAppTickets = (clone $ticketQuery)
            ->where(function ($q) {
                $q->whereNull('agent_id')
                    ->orWhere('source', 'self');
            });

        $salesChannels = collect([
            (object) [
                'name' => 'Mini App',
                'is_mini_app' => true,
                'tickets_count' => (clone $miniAppTickets)->count(),
                'tickets_sum_price_paid' => (clone $miniAppTickets)->sum('price_paid'),
            ],
        ])->concat($agentStats->map(fn (User $agent) => (object) [
            'name' => $agent->name,
            'is_mini_app' => false,
            'tickets_count' => $agent->tickets_count,
            'tickets_sum_price_paid' => $agent->tickets_sum_price_paid,
        ]))->sortByDesc('tickets_count')->values();

        $summary = [
            'events'  => Event::forCompany($companyId)->count(),
            'tickets' => (clone $ticketQuery)->count(),
            'revenue' => (clone $ticketQuery)->sum('price_paid'),
        ];

        return view('admin.dashboard', compact('events', 'agentStats', 'salesChannels', 'summary'));
    }
}
