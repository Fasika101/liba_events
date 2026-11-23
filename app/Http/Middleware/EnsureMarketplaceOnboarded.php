<?php

namespace App\Http\Middleware;

use App\Models\Attendee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketplaceOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $attendeeId = $request->session()->get('public_attendee_id');
        if (! $attendeeId) {
            return $next($request);
        }

        $attendee = Attendee::query()->find($attendeeId);
        if (! $attendee || $attendee->isOnboarded()) {
            return $next($request);
        }

        // Telegram guests without a linked telegram_id can still browse/checkout in browser.
        if (! filled($attendee->telegram_id)) {
            return $next($request);
        }

        return redirect()->route('public.onboarding');
    }
}
