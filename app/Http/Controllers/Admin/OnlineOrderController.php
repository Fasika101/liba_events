<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketOrder;
use App\Services\TicketOrderService;
use Illuminate\Http\Request;
use RuntimeException;

class OnlineOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->requireCompanyId();
        $status = $request->string('status')->toString();

        $orders = TicketOrder::query()
            ->with(['event', 'attendee', 'ticket'])
            ->where('company_id', $companyId)
            ->when($status !== '' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = TicketOrder::query()
            ->where('company_id', $companyId)
            ->where('status', TicketOrder::STATUS_PENDING)
            ->count();

        return view('admin.online-orders.index', compact('orders', 'pendingCount', 'status'));
    }

    public function show(TicketOrder $onlineOrder)
    {
        $this->authorizeOrder($onlineOrder);
        $onlineOrder->load(['event', 'attendee', 'ticket', 'reviewer']);

        return view('admin.online-orders.show', ['order' => $onlineOrder]);
    }

    public function markPaid(Request $request, TicketOrder $onlineOrder, TicketOrderService $orders)
    {
        $this->authorizeOrder($onlineOrder);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $orders->markPaid($onlineOrder, auth()->user(), $data['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order marked paid. Ticket issued and SMS attempted.');
    }

    public function reject(Request $request, TicketOrder $onlineOrder, TicketOrderService $orders)
    {
        $this->authorizeOrder($onlineOrder);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $orders->markRejected($onlineOrder, auth()->user(), $data['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Order rejected.');
    }

    private function authorizeOrder(TicketOrder $order): void
    {
        if ((int) $order->company_id !== auth()->user()->requireCompanyId()) {
            abort(403);
        }
    }
}
