<?php

namespace App\Http\Middleware;

use App\Models\Attendee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTelegramMarketplaceUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $attendeeId = $request->session()->get('public_attendee_id');
        $attendee = $attendeeId ? Attendee::query()->find($attendeeId) : null;

        if (! $attendee || ! filled($attendee->telegram_id)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => __('public.onboarding_need_telegram'),
                    'redirect' => route('public.gate'),
                ], 401);
            }

            return redirect()->route('public.gate');
        }

        if (! $attendee->isOnboarded()) {
            return redirect()->route('public.onboarding');
        }

        return $next($request);
    }
}
