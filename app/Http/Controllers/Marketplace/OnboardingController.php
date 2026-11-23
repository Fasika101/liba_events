<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Services\TelegramBotClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function show(Request $request, TelegramBotClient $bot)
    {
        $attendee = $this->currentAttendee($request);

        if (! $attendee) {
            return view('public.onboarding.need-telegram');
        }

        if ($attendee->isOnboarded()) {
            return redirect()->route('public.discover');
        }

        $channelRequired = $bot->channelConfigured();
        $needsChannel = $channelRequired && $attendee->needsChannelJoin();
        $needsProfile = $attendee->needsProfile();

        return view('public.onboarding.show', [
            'attendee' => $attendee,
            'channelRequired' => $channelRequired,
            'needsChannel' => $needsChannel,
            'needsProfile' => $needsProfile,
            'channelUrl' => $bot->channelUrl(),
            'botUsername' => config('services.telegram.bot_username'),
        ]);
    }

    public function checkChannel(Request $request, TelegramBotClient $bot)
    {
        $attendee = $this->requireAttendee($request);

        if (! $bot->channelConfigured()) {
            $attendee->forceFill(['channel_joined_at' => now()])->save();

            return response()->json([
                'ok' => true,
                'joined' => true,
                'needs_profile' => $attendee->needsProfile(),
            ]);
        }

        if (! filled($attendee->telegram_id)) {
            return response()->json([
                'ok' => false,
                'joined' => false,
                'message' => __('public.onboarding_need_telegram'),
            ], 422);
        }

        $joined = $bot->isChannelMember($attendee->telegram_id);
        if ($joined) {
            $attendee->forceFill(['channel_joined_at' => now()])->save();
        }

        return response()->json([
            'ok' => true,
            'joined' => $joined,
            'needs_profile' => $attendee->fresh()->needsProfile(),
            'message' => $joined ? null : __('public.onboarding_channel_not_joined'),
        ]);
    }

    public function register(Request $request, TelegramBotClient $bot)
    {
        $attendee = $this->requireAttendee($request);

        if ($bot->channelConfigured() && $attendee->needsChannelJoin()) {
            throw ValidationException::withMessages([
                'channel' => __('public.onboarding_join_first'),
            ]);
        }

        $this->normalizePhone($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'buyer_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'locale' => ['nullable', 'in:en,am'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ], [
            'buyer_phone.regex' => 'Phone must be 9 digits after +251.',
        ]);

        $locale = $data['locale'] ?? $request->session()->get('public_locale', 'en');

        $attendee->update([
            'name' => $data['name'],
            'phone' => $data['buyer_phone'],
            'email' => $data['email'] ?? null,
            'locale' => in_array($locale, ['en', 'am'], true) ? $locale : 'en',
            'notifications_enabled' => $request->boolean('notifications_enabled'),
            'profile_completed_at' => now(),
        ]);

        $request->session()->put('public_locale', $attendee->locale);
        $request->session()->put('public_attendee_id', $attendee->id);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('public.discover'),
            ]);
        }

        return redirect()->route('public.discover')
            ->with('status', __('public.onboarding_done'));
    }

    private function normalizePhone(Request $request): void
    {
        $local = preg_replace('/\D+/', '', (string) $request->input('buyer_phone_local', ''));
        if (str_starts_with($local, '0')) {
            $local = substr($local, 1);
        }
        $local = substr($local, 0, 9);

        if (strlen($local) === 9) {
            $request->merge(['buyer_phone' => '+251'.$local]);
        }
    }

    private function currentAttendee(Request $request): ?Attendee
    {
        $id = $request->session()->get('public_attendee_id');

        return $id ? Attendee::query()->find($id) : null;
    }

    private function requireAttendee(Request $request): Attendee
    {
        $attendee = $this->currentAttendee($request);
        if (! $attendee) {
            throw ValidationException::withMessages([
                'attendee' => __('public.onboarding_need_telegram'),
            ]);
        }

        return $attendee;
    }
}
