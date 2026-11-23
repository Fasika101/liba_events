<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotClient;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;

class TelegramBroadcastController extends Controller
{
    public function index(TelegramBotClient $bot, TelegramNotifier $notifier)
    {
        return view('super-admin.telegram-broadcast.index', [
            'botConfigured' => $bot->configured(),
            'channelConfigured' => $bot->channelConfigured(),
            'channelUrl' => $bot->channelUrl(),
            'channelId' => config('services.telegram.channel_id'),
            'botUsername' => config('services.telegram.bot_username'),
            'usersEligible' => $notifier->notifiableAttendeesCount(),
        ]);
    }

    public function send(Request $request, TelegramBotClient $bot, TelegramNotifier $notifier)
    {
        if (! $bot->configured()) {
            return back()
                ->with('error', 'TELEGRAM_BOT_TOKEN is not set.')
                ->withInput();
        }

        $data = $request->validate([
            'message_en' => ['required', 'string', 'min:3', 'max:3500'],
            'message_am' => ['nullable', 'string', 'max:3500'],
            'to_users' => ['nullable', 'boolean'],
            'to_channel' => ['nullable', 'boolean'],
        ]);

        $toUsers = $request->boolean('to_users');
        $toChannel = $request->boolean('to_channel');

        if (! $toUsers && ! $toChannel) {
            return back()
                ->with('error', 'Choose at least one destination: users or channel.')
                ->withInput();
        }

        $result = $notifier->broadcast(
            $data['message_en'],
            $data['message_am'] ?? null,
            $toUsers,
            $toChannel,
        );

        $parts = [];
        if ($toUsers) {
            $parts[] = "{$result['users_sent']} of {$result['users_eligible']} users messaged";
        }
        if ($toChannel) {
            $parts[] = $result['channel_posted']
                ? 'posted to channel'
                : ('channel failed'.($result['channel_error'] ? ': '.$result['channel_error'] : ''));
        }

        $summary = 'Broadcast finished: '.implode('; ', $parts).'.';

        if ($toChannel && ! $result['channel_posted']) {
            return back()->with('error', $summary)->withInput();
        }

        return back()->with('status', $summary);
    }
}
