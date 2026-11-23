<?php

namespace App\Console\Commands;

use App\Services\TelegramBotClient;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url?}';

    protected $description = 'Point the Telegram bot webhook at this app (HTTPS required)';

    public function handle(TelegramBotClient $bot): int
    {
        if (! $bot->configured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');

            return 1;
        }

        $url = $this->argument('url') ?: rtrim((string) config('app.url'), '/').'/webhooks/telegram';
        $secret = config('services.telegram.webhook_secret');

        $result = $bot->setWebhook($url, $secret);

        if ($result['ok'] ?? false) {
            $this->info("Webhook set to: {$url}");

            return 0;
        }

        $this->error('Failed: '.($result['description'] ?? json_encode($result)));

        return 1;
    }
}
