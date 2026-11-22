<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\TicketOrder;
use App\Services\TelegramWebAppAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $attendee = $this->currentAttendee($request);

        $orders = collect();
        if ($attendee) {
            $orders = TicketOrder::query()
                ->with(['event.company', 'ticket'])
                ->where('attendee_id', $attendee->id)
                ->latest()
                ->get();
        }

        return view('public.profile.show', compact('attendee', 'orders'));
    }

    public function authTelegram(Request $request, TelegramWebAppAuth $telegram)
    {
        $data = $request->validate([
            'initData' => ['required', 'string'],
            'locale' => ['nullable', 'in:en,am'],
        ]);

        $user = $telegram->validateInitData($data['initData']);
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'Invalid Telegram session'], 422);
        }

        $attendee = $telegram->upsertAttendeeFromTelegramUser(
            $user,
            $data['locale'] ?? $request->session()->get('public_locale', 'en')
        );

        $request->session()->put('public_attendee_id', $attendee->id);

        return response()->json([
            'ok' => true,
            'attendee' => [
                'id' => $attendee->id,
                'name' => $attendee->displayName(),
            ],
        ]);
    }

    public function setLocale(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,am'],
        ]);

        $request->session()->put('public_locale', $data['locale']);

        if ($attendeeId = $request->session()->get('public_attendee_id')) {
            Attendee::query()->whereKey($attendeeId)->update(['locale' => $data['locale']]);
        }

        return back();
    }

    public function ticketLink(Request $request, string $publicCode)
    {
        $order = TicketOrder::query()
            ->with('ticket')
            ->where('public_code', $publicCode)
            ->where('status', TicketOrder::STATUS_PAID)
            ->firstOrFail();

        if (! $order->ticket) {
            abort(404);
        }

        $url = URL::signedRoute('tickets.public-receipt', ['ticket' => $order->ticket->id], now()->addDays(30));

        return redirect($url);
    }

    private function currentAttendee(Request $request): ?Attendee
    {
        $id = $request->session()->get('public_attendee_id');

        return $id ? Attendee::query()->find($id) : null;
    }
}
