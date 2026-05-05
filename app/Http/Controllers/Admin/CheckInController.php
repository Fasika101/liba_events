<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    /** List of events for check-in (admin & agent). */
    public function index()
    {
        $companyId = auth()->user()->requireCompanyId();

        $events = Event::forCompany($companyId)
            ->withCount([
                'tickets',
                'tickets as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            ])
            ->latest('start_at')
            ->get();

        $showRouteName = $this->showRouteName();

        return view('admin.checkin.index', compact('events', 'showRouteName'));
    }

    /** Live check-in scanning page for one event (admin & agent). */
    public function show(Event $event)
    {
        $companyId = auth()->user()->requireCompanyId();
        abort_if((int) $event->company_id !== $companyId, 403);

        $totalTickets   = $event->tickets()->count();
        $checkedInCount = $event->tickets()->whereNotNull('checked_in_at')->count();

        $allCheckIns = Ticket::with('agent')
            ->where('event_id', $event->id)
            ->whereNotNull('checked_in_at')
            ->latest('checked_in_at')
            ->get();

        $verifyUrl  = route($this->verifyRouteName(), $event);
        $indexRoute = $this->indexRouteName();

        return view('admin.checkin.show', compact(
            'event', 'totalTickets', 'checkedInCount', 'allCheckIns', 'verifyUrl', 'indexRoute'
        ));
    }

    /** AJAX: verify a ticket code and mark it as checked in (admin & agent). */
    public function verify(Request $request, Event $event): JsonResponse
    {
        $companyId = auth()->user()->requireCompanyId();
        abort_if((int) $event->company_id !== $companyId, 403);

        $code = strtoupper(trim($request->input('code', '')));

        if ($code === '') {
            return response()->json(['status' => 'invalid', 'message' => 'No ticket code provided.']);
        }

        $ticket = Ticket::with('event')
            ->where('ticket_code', $code)
            ->first();

        if (! $ticket) {
            return response()->json(['status' => 'invalid', 'message' => 'Ticket not found. Invalid code.']);
        }

        if ((int) $ticket->event_id !== (int) $event->id) {
            return response()->json([
                'status'  => 'wrong_event',
                'message' => 'This ticket is for a different event: "' . ($ticket->event->title ?? '?') . '".',
            ]);
        }

        if ($ticket->checked_in_at) {
            return response()->json([
                'status'        => 'already_used',
                'message'       => 'Already checked in at ' . $ticket->checked_in_at->format('H:i') . '.',
                'buyer_name'    => $ticket->buyer_name,
                'buyer_phone'   => $ticket->buyer_phone ?? '—',
                'ticket_code'   => $ticket->ticket_code,
                'checked_in_at' => $ticket->checked_in_at->format('H:i'),
            ]);
        }

        $ticket->update(['checked_in_at' => now()]);

        return response()->json([
            'status'      => 'valid',
            'message'     => 'Valid ticket — entry granted!',
            'buyer_name'  => $ticket->buyer_name,
            'buyer_phone' => $ticket->buyer_phone ?? '—',
            'ticket_code' => $ticket->ticket_code,
        ]);
    }

    private function showRouteName(): string
    {
        return auth()->user()->isAdmin() ? 'admin.checkin.show' : 'agent.checkin.show';
    }

    private function verifyRouteName(): string
    {
        return auth()->user()->isAdmin() ? 'admin.checkin.verify' : 'agent.checkin.verify';
    }

    private function indexRouteName(): string
    {
        return auth()->user()->isAdmin() ? 'admin.checkin.index' : 'agent.checkin.index';
    }
}
