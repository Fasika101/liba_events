<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomersExport;
use App\Helpers\EthiopianCalendar;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Customers page (DataTables loads rows via AJAX).
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->requireCompanyId();

        $totalUnique = Ticket::whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->whereHas('event', fn ($q) => $q->where('company_id', $companyId))
            ->distinct('buyer_phone')
            ->count('buyer_phone');

        $initialSearch = $request->input('search', '');

        return view('admin.customers.index', compact('totalUnique', 'initialSearch'));
    }

    /**
     * Server-side JSON for DataTables.
     */
    public function data(Request $request): JsonResponse
    {
        $companyId = auth()->user()->requireCompanyId();

        $latestIds = Ticket::select(DB::raw('MAX(id) as id'))
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->whereHas('event', fn ($q) => $q->where('company_id', $companyId))
            ->groupBy('buyer_phone');

        $recordsTotal = Ticket::query()
            ->joinSub($latestIds, 'latest', fn($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $companyId)
            ->count();

        $query = $this->customersDataQuery($latestIds, $companyId);
        $search = $request->input('search.value');
        if ($search !== null && $search !== '') {
            $this->applyCustomersSearch($query, $search);
        }

        $recordsFiltered = (clone $query)->count();

        $orderCol = (int) $request->input('order.0.column', 8);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderMap = [
            0 => 'tickets.id',
            1 => 'tickets.buyer_name',
            2 => 'tickets.buyer_phone',
            3 => 'tickets.buyer_email',
            4 => 'tickets.buyer_address',
            5 => 'tickets.buyer_occupation',
            6 => 'tickets.yeneshaa_abat',
            7 => 'events.title',
            8 => 'tickets.sold_at',
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
            $eventTitle = e($c->event->title ?? '—');

            $data[] = [
                $rowIndex,
                '<span class="font-weight-bold">' . e($c->buyer_name) . '</span>',
                $phoneDisplay,
                $emailDisplay,
                e($c->buyer_address ?: '—'),
                e($c->buyer_occupation ?: '—'),
                $c->yeneshaa_abat ? 'Yes' : 'No',
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

    /**
     * Export unique customers as Excel (one row per phone number).
     */
    public function export(Request $request)
    {
        $companyId = auth()->user()->requireCompanyId();

        $latestIds = Ticket::select(DB::raw('MAX(id) as id'))
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '')
            ->whereHas('event', fn ($q) => $q->where('company_id', $companyId))
            ->groupBy('buyer_phone');

        $query = $this->customersDataQuery($latestIds, $companyId);
        if ($search = $request->input('search')) {
            $this->applyCustomersSearch($query, $search);
        }

        $query->orderBy('tickets.sold_at', 'desc');
        $customers = $query->get();
        $filename  = 'customers-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new CustomersExport($customers), $filename);
    }

    private function customersDataQuery($latestIds, int $companyId): Builder
    {
        return Ticket::query()
            ->select('tickets.*')
            ->with(['event', 'agent'])
            ->joinSub($latestIds, 'latest', fn($j) => $j->on('tickets.id', '=', 'latest.id'))
            ->leftJoin('events', 'tickets.event_id', '=', 'events.id')
            ->where('events.company_id', $companyId);
    }

    private function applyCustomersSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('tickets.buyer_name', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_email', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_phone', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_address', 'like', "%{$search}%")
                ->orWhere('tickets.buyer_occupation', 'like', "%{$search}%");
        });
    }
}
