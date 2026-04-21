<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Event;

class DashboardController extends Controller
{
    public function index()
    {
        $agent = auth()->user();
        $companyId = $agent->requireCompanyId();

        $stats = [
            'tickets' => $agent->tickets()->count(),
            'revenue' => $agent->tickets()->sum('price_paid'),
            'events'  => Event::where('status', 'active')->where('company_id', $companyId)->count(),
        ];

        $recentTickets = $agent->tickets()
            ->with('event')
            ->latest()
            ->take(10)
            ->get();

        $events = Event::where('status', 'active')
            ->where('company_id', $companyId)
            ->withCount('tickets')
            ->orderBy('start_at')
            ->get();

        return view('agent.dashboard', compact('stats', 'recentTickets', 'events'));
    }
}
