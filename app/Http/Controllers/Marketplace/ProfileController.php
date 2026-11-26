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

        return view('public.profile.account', compact('attendee'));
    }

    public function tickets(Request $request)
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

        return view('public.profile.tickets', compact('attendee', 'orders'));
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

        $needsOnboarding = ! $attendee->isOnboarded();

        return response()->json([
            'ok' => true,
            'attendee' => [
                'id' => $attendee->id,
                'name' => $attendee->displayName(),
            ],
            'onboarding' => [
                'required' => $needsOnboarding,
                'needs_channel' => $attendee->needsChannelJoin(),
                'needs_profile' => $attendee->needsProfile(),
                'redirect' => route('public.onboarding'),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $attendee = $this->currentAttendee($request);
        if (! $attendee || ! $attendee->isOnboarded()) {
            return redirect()->route('public.onboarding');
        }

        $local = preg_replace('/\D+/', '', (string) $request->input('buyer_phone_local', ''));
        if (str_starts_with($local, '0')) {
            $local = substr($local, 1);
        }
        $local = substr($local, 0, 9);
        if (strlen($local) === 9) {
            $request->merge(['buyer_phone' => '+251'.$local]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'buyer_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ], [
            'buyer_phone.regex' => 'Phone must be 9 digits after +251.',
        ]);

        $attendee->update([
            'name' => $data['name'],
            'phone' => $data['buyer_phone'],
            'email' => $data['email'] ?? null,
            'notifications_enabled' => $request->boolean('notifications_enabled'),
        ]);

        return back()->with('status', __('public.profile_updated'));
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
