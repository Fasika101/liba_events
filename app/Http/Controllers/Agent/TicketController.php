<?php

namespace App\Http\Controllers\Agent;

use App\Helpers\EthiopianCalendar;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\CustomerTicketSmsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $agent = auth()->user();

        $agentEvents = Event::whereHas('tickets', fn($q) => $q->where('agent_id', $agent->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        $selectedEventId = $request->input('event_id', '');

        return view('agent.tickets.index', compact('agentEvents', 'selectedEventId'));
    }

    /**
     * Server-side JSON for DataTables (My Tickets).
     */
    public function data(Request $request): JsonResponse
    {
        $agentId = (int) auth()->id();

        $recordsTotal = Ticket::where('agent_id', $agentId)->count();

        $query = $this->agentTicketsBaseQuery($agentId);

        if ($eventId = $request->input('event_id')) {
            $query->where('tickets.event_id', (int) $eventId);
        }

        $search = $request->input('search.value');
        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tickets.buyer_name', 'like', "%{$search}%")
                    ->orWhere('tickets.buyer_email', 'like', "%{$search}%")
                    ->orWhere('tickets.buyer_phone', 'like', "%{$search}%")
                    ->orWhere('tickets.ticket_code', 'like', "%{$search}%")
                    ->orWhere('events.title', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 4);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderMap = [
            0 => 'events.title',
            1 => 'tickets.buyer_name',
            2 => 'tickets.buyer_phone',
            3 => 'tickets.price_paid',
            4 => 'tickets.sold_at',
            5 => 'tickets.ticket_code',
        ];
        $query->orderBy($orderMap[$orderCol] ?? 'tickets.sold_at', $orderDir);

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length === -1) {
            $rows = $query->get();
        } else {
            $length = max(1, min($length, 500));
            $rows = $query->skip($start)->take($length)->get();
        }

        $data = [];
        foreach ($rows as $t) {
            $buyerBlock = '<span class="font-weight-bold">' . e($t->buyer_name) . '</span>';
            if ($t->buyer_email) {
                $buyerBlock .= '<br><small class="text-muted">' . e($t->buyer_email) . '</small>';
            }
            $phoneCell = $t->buyer_phone
                ? '<a href="tel:' . e($t->buyer_phone) . '" class="text-dark">' . e($t->buyer_phone) . '</a>'
                : '<span class="text-muted">—</span>';
            $priceCell = '<span class="badge badge-success">' . e((string) $t->price_paid) . ' ' . e($t->currency) . '</span>';
            $receiptUrl = route('agent.tickets.receipt', $t);
            $receiptCell = '<a href="' . e($receiptUrl) . '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="View & print receipt">'
                . '<i class="fas fa-receipt"></i></a>';

            $data[] = [
                '<span class="font-weight-bold">' . e($t->event->title ?? '—') . '</span>',
                $buyerBlock,
                $phoneCell,
                $priceCell,
                '<span class="text-muted">' . e(EthiopianCalendar::format($t->sold_at)) . '</span>',
                '<code>' . e($t->ticket_code) . '</code>',
                $receiptCell,
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    private function agentTicketsBaseQuery(int $agentId): Builder
    {
        return Ticket::query()
            ->select('tickets.*')
            ->where('tickets.agent_id', $agentId)
            ->with('event')
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id');
    }

    public function create()
    {
        $companyId = auth()->user()->requireCompanyId();

        $events = Event::where('status', 'active')
            ->where('company_id', $companyId)
            ->withCount('tickets')
            ->orderBy('start_at')
            ->get();

        return view('agent.tickets.create', compact('events'));
    }

    public function store(Request $request, CustomerTicketSmsService $customerSms)
    {
        $companyId = auth()->user()->requireCompanyId();

        $data = $request->validate([
            'event_id'      => [
                'required',
                Rule::exists('events', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'buyer_name'    => ['required', 'string', 'max:255'],
            'buyer_email'   => ['nullable', 'email', 'max:255'],
            'buyer_phone'   => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'buyer_address'   => ['nullable', 'string', 'max:500'],
            'buyer_occupation' => ['nullable', 'string', 'max:150'],
            'yeneshaa_abat'    => ['required', 'in:0,1'],
        ], [
            'buyer_phone.required' => 'Phone number is required.',
            'buyer_phone.regex'    => 'Phone number must be exactly 9 digits after +251 (e.g. +251912345678).',
            'yeneshaa_abat.required' => 'Please select Yes or No for Yeneshaa Abat.',
            'yeneshaa_abat.in'       => 'Yeneshaa Abat must be Yes or No.',
        ]);

        $event = Event::findOrFail($data['event_id']);

        if ($event->capacity > 0 && $event->tickets()->count() >= $event->capacity) {
            return back()->withErrors(['event_id' => 'This event is at full capacity.'])->withInput();
        }

        $ticket = DB::transaction(function () use ($data, $event) {
            $company = $event->company;

            return Ticket::create([
                'event_id'         => $event->id,
                'agent_id'         => auth()->id(),
                'buyer_name'       => $data['buyer_name'],
                'buyer_email'      => $data['buyer_email'] ?? null,
                'buyer_phone'      => $data['buyer_phone'] ?? null,
                'buyer_address'    => $data['buyer_address'] ?? null,
                'buyer_occupation' => $data['buyer_occupation'] ?? null,
                'yeneshaa_abat'    => (bool) (int) $data['yeneshaa_abat'],
                'price_paid'       => $event->price,
                'currency'         => $event->currency,
                'ticket_code'      => Ticket::generateCode($event->company_id, $company->name),
                'sold_at'          => now(),
            ]);
        });

        try {
            $customerSms->sendForTicketSale($ticket);
        } catch (\Throwable) {
            // Ticket sale succeeds even if customer SMS fails (e.g. no credits).
        }

        return redirect()->route('agent.tickets.receipt', $ticket);
    }

    public function receipt(Ticket $ticket)
    {
        // Only the agent who sold it can view the receipt
        if ($ticket->agent_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load('event', 'agent');

        return view('agent.tickets.receipt', compact('ticket'));
    }
}
