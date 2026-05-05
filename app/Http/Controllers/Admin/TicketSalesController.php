<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EventBuyersExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TicketSalesController extends Controller
{
    /** Overview: one card per event with totals */
    public function index()
    {
        $companyId = auth()->user()->requireCompanyId();

        $events = Event::withTrashed()
            ->forCompany($companyId)
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->orderByDesc('tickets_count')
            ->get();

        $totalTickets = $events->sum('tickets_count');
        $totalRevenue = $events->sum('tickets_sum_price_paid');

        return view('admin.ticket-sales.index', compact('events', 'totalTickets', 'totalRevenue'));
    }

    /** Detail: all buyers for a single event */
    public function show(Event $event)
    {
        $tickets = Ticket::with('agent')
            ->where('event_id', $event->id)
            ->latest('sold_at')
            ->get();

        $totalRevenue = $tickets->sum('price_paid');
        $agentSummary = $tickets->groupBy('agent_id')->map(fn($t) => [
            'name'    => $t->first()->agent->name ?? 'Unknown',
            'count'   => $t->count(),
            'revenue' => $t->sum('price_paid'),
        ])->sortByDesc('count');

        return view('admin.ticket-sales.show', compact('event', 'tickets', 'totalRevenue', 'agentSummary'));
    }

    /** Export ALL buyers for a single event as Excel */
    public function export(Event $event)
    {
        $tickets = Ticket::with('agent')
            ->where('event_id', $event->id)
            ->latest('sold_at')
            ->get();

        $event->loadMissing('company');
        $filename = 'buyers-' . \Illuminate\Support\Str::slug($event->title) . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new EventBuyersExport($event, $tickets), $filename);
    }

    /** Export with chosen columns (POST with columns[]) */
    public function exportSelected(Request $request, Event $event)
    {
        $columns = array_filter((array) $request->input('columns', []));

        $tickets = Ticket::with('agent')
            ->where('event_id', $event->id)
            ->latest('sold_at')
            ->get();

        $event->loadMissing('company');

        $colCount = count($columns) ?: 'all';
        $filename = 'buyers-' . \Illuminate\Support\Str::slug($event->title)
                  . '-cols-' . $colCount . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new EventBuyersExport($event, $tickets, $columns), $filename);
    }

    public function edit(Event $event, Ticket $ticket)
    {
        $this->ensureTicketForEvent($event, $ticket);

        return view('admin.ticket-sales.edit', compact('event', 'ticket'));
    }

    public function update(Request $request, Event $event, Ticket $ticket)
    {
        $this->ensureTicketForEvent($event, $ticket);

        $data = $request->validate([
            'buyer_name'    => ['required', 'string', 'max:255'],
            'buyer_email'   => ['nullable', 'email', 'max:255'],
            'buyer_phone'   => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'buyer_address'   => ['nullable', 'string', 'max:500'],
            'buyer_occupation' => ['nullable', 'string', 'max:150'],
            'yeneshaa_abat'    => ['required', 'in:0,1'],
            'price_paid'    => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'size:3'],
        ], [
            'buyer_phone.required' => 'Phone number is required.',
            'buyer_phone.regex'    => 'Phone number must be exactly 9 digits after +251 (e.g. +251912345678).',
            'yeneshaa_abat.required' => 'Please select Yes or No for Yeneshaa Abat.',
            'yeneshaa_abat.in'       => 'Yeneshaa Abat must be Yes or No.',
        ]);

        // agent_id / event_id / ticket_code / sold_at are immutable — admin cannot reassign who sold the ticket.
        $ticket->update([
            'buyer_name'    => $data['buyer_name'],
            'buyer_email'   => $data['buyer_email'] ?? null,
            'buyer_phone'   => $data['buyer_phone'],
            'buyer_address'   => $data['buyer_address'] ?? null,
            'buyer_occupation' => $data['buyer_occupation'] ?? null,
            'yeneshaa_abat'  => (bool) (int) $data['yeneshaa_abat'],
            'price_paid'    => $data['price_paid'],
            'currency'      => strtoupper($data['currency']),
        ]);

        return redirect()->route('admin.ticket-sales.show', $event)->with('status', 'Ticket updated successfully.');
    }

    private function ensureTicketForEvent(Event $event, Ticket $ticket): void
    {
        if ((int) $ticket->event_id !== (int) $event->id) {
            abort(404);
        }
    }
}
