<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Services\TicketOrderService;
use Illuminate\Http\Request;
use RuntimeException;

class CheckoutController extends Controller
{
    public function store(Request $request, Event $event, TicketOrderService $orders)
    {
        $event->load('company');

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'buyer_address' => ['nullable', 'string', 'max:500'],
            'buyer_occupation' => ['nullable', 'string', 'max:150'],
            'yeneshaa_abat' => ['required', 'in:0,1'],
            'bank_code' => ['nullable', 'string', 'max:40'],
            'transaction_reference' => ['required', 'string', 'max:120'],
        ], [
            'buyer_phone.regex' => 'Phone must be +251 followed by 9 digits.',
            'transaction_reference.required' => 'Enter the bank transaction / receipt ID.',
        ]);

        // Prevent obvious duplicate pending claims with same reference for same company.
        $duplicate = TicketOrder::query()
            ->where('company_id', $event->company_id)
            ->where('transaction_reference', $data['transaction_reference'])
            ->whereIn('status', [TicketOrder::STATUS_PENDING, TicketOrder::STATUS_PAID])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'transaction_reference' => 'This transaction ID was already submitted.',
            ])->withInput();
        }

        $attendee = $this->resolveAttendee($request, $data);

        try {
            $order = $orders->createPendingOrder($event, $data, $attendee);
        } catch (RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }

        $request->session()->put('public_attendee_id', $attendee->id);

        return redirect()->route('public.orders.show', $order->public_code)
            ->with('status', __('public.order_pending_title'));
    }

    public function show(string $publicCode)
    {
        $order = TicketOrder::query()
            ->with(['event.company', 'ticket'])
            ->where('public_code', $publicCode)
            ->firstOrFail();

        $this->ensureCanView($order);

        return view('public.orders.show', compact('order'));
    }

    private function resolveAttendee(Request $request, array $data): Attendee
    {
        $attendeeId = $request->session()->get('public_attendee_id');
        if ($attendeeId) {
            $attendee = Attendee::query()->find($attendeeId);
            if ($attendee) {
                $attendee->update([
                    'name' => $data['buyer_name'],
                    'phone' => $data['buyer_phone'],
                ]);

                return $attendee;
            }
        }

        return Attendee::create([
            'name' => $data['buyer_name'],
            'phone' => $data['buyer_phone'],
            'locale' => $request->session()->get('public_locale', 'en'),
        ]);
    }

    private function ensureCanView(TicketOrder $order): void
    {
        $attendeeId = session('public_attendee_id');
        if ($attendeeId && (int) $order->attendee_id === (int) $attendeeId) {
            return;
        }

        // Allow viewing by public code link (shared from SMS / Telegram).
    }
}
