<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetTelegramMenuButton extends Command
{
    protected $signature = 'telegram:set-menu-button';

    protected $description = 'Set the Telegram bot menu button to open the login page directly';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        $loginUrl = url('/login');

        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return 1;
        }

        $menuButton = [
            'type' => 'web_app',
            'text' => 'Open App',
            'web_app' => [
                'url' => $loginUrl,
            ],
        ];

        $url = "https://api.telegram.org/bot{$token}/setChatMenuButton";
        $response = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode(['menu_button' => $menuButton]),
            ],
        ]));

        $data = json_decode($response, true);

        if ($data['ok'] ?? false) {
            $this->info("Menu button set successfully. Agents can now tap 'Open App' in the bot to open: {$loginUrl}");
            return 0;
        }

        $this->error('Failed: ' . ($data['description'] ?? $response));
        return 1;
    }
}
