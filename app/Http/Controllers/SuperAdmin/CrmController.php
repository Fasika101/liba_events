<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\CrmCustomersExport;
use App\Helpers\EthiopianCalendar;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $totalUnique = DB::query()
            ->fromSub($this->latestTicketIdsSubquery(), 'latest')
            ->count();

        $initialSearch = $request->input('search', '');

        return view('super-admin.crm.index', compact('totalUnique', 'initialSearch'));
    }

    public function data(Request $request): JsonResponse
    {
        $latestIds = $this->latestTicketIdsSubquery();

        $recordsTotal = Ticket::query()
            ->joinSub($latestIds, 'latest', fn ($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->leftJoin('companies', 'events.company_id', '=', 'companies.id')
            ->count();

        $query = $this->crmDataQuery($latestIds);
        $search = $request->input('search.value');
        if ($search !== null && $search !== '') {
            $this->applyCrmSearch($query, $search);
        }

        $recordsFiltered = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 9);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderMap = [
            0 => 'tickets.id',
            1 => 'tickets.buyer_name',
            2 => 'tickets.buyer_phone',
            3 => 'tickets.buyer_email',
            4 => 'tickets.buyer_address',
            5 => 'tickets.buyer_occupation',
            6 => 'tickets.yeneshaa_abat',
            7 => 'companies.name',
            8 => 'events.title',
            9 => 'tickets.sold_at',
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
        foreach ($rows as $i => $c) {
            $rowIndex = $start + $i + 1;
            $phoneDisplay = $c->buyer_phone
                ? '<a href="tel:' . e($c->buyer_phone) . '" class="text-dark font-weight-bold">'
                    . '<i class="fas fa-phone-alt mr-1 text-success" style="font-size:.8rem;"></i>'
                    . e($c->buyer_phone) . '</a>'
                : '<span class="text-muted">—</span>';
            $emailDisplay = $c->buyer_email
                ? '<a href="mailto:' . e($c->buyer_email) . '" class="text-muted">' . e($c->buyer_email) . '</a>'
                : '<span class="text-muted">—</span>';
            $eventTitle = e($c->event?->title ?? '—');
            $companyName = e($c->event?->company?->name ?? '—');

            $data[] = [
                $rowIndex,
                '<span class="font-weight-bold">' . e($c->buyer_name) . '</span>',
                $phoneDisplay,
                $emailDisplay,
                e($c->buyer_address ?: '—'),
                e($c->buyer_occupation ?: '—'),
                $c->yeneshaa_abat ? 'Yes' : 'No',
                '<span class="badge badge-primary">' . $companyName . '</span>',
                '<span class="badge badge-light border">' . $eventTitle . '</span>',
                '<span class="text-muted">' . e(EthiopianCalendar::format($c->sold_at)) . '</span>',
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    public function export(Request $request)
    {
        $latestIds = $this->latestTicketIdsSubquery();

        $query = $this->crmDataQuery($latestIds);
        if ($search = $request->input('search')) {
            $this->applyCrmSearch($query, $search);
        }

        $query->orderBy('tickets.sold_at', 'desc');
        $customers = $query->get();
        $filename = 'crm-customers-all-companies-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new CrmCustomersExport($customers), $filename);
    }

    /**
     * Latest ticket id per (company, buyer_phone) — same person in two companies = two rows.
     */
    private function latestTicketIdsSubquery(): QueryBuilder
    {
        return Ticket::query()
            ->select(DB::raw('MAX(tickets.id) as id'))
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->whereNotNull('tickets.buyer_phone')
            ->where('tickets.buyer_phone', '!=', '')
            ->groupBy('events.company_id', 'tickets.buyer_phone')
            ->getQuery();
    }

    private function crmDataQuery(QueryBuilder $latestIds): Builder
    {
        return Ticket::query()
            ->select('tickets.*')
            ->with(['event.company', 'agent'])
            ->joinSub($latestIds, 'latest', fn ($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->leftJoin('companies', 'events.company_id', '=', 'companies.id');
    }

    private function applyCrmSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('tickets.buyer_name', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_email', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_phone', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_address', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_occupation', 'like', "%{$search}%")
                ->orWhere('companies.name', 'like', "%{$search}%")
                ->orWhere('events.title', 'like', "%{$search}%");
        });
    }
}
