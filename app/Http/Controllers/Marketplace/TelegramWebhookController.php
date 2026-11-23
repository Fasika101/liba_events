<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramNotifier $notifier): Response
    {
        $secret = config('services.telegram.webhook_secret');
        if (filled($secret)) {
            $header = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (! hash_equals((string) $secret, (string) $header)) {
                abort(403);
            }
        }

        $message = $request->input('message');
        if (! is_array($message)) {
            return response('ok');
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));
        if (! $chatId || $text === '') {
            return response('ok');
        }

        $command = strtolower(strtok($text, ' @') ?: '');
        if (in_array($command, ['/start', '/help'], true)) {
            $locale = str_contains(strtolower($text), 'am') ? 'am' : 'en';
            $notifier->sendWelcome($chatId, $locale);
        }

        return response('ok');
    }
}
