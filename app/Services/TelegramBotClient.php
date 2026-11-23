<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotClient
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    public function channelConfigured(): bool
    {
        return filled(config('services.telegram.channel_id'));
    }

    public function channelUrl(): ?string
    {
        $username = ltrim((string) config('services.telegram.channel_username'), '@');
        if ($username !== '') {
            return 'https://t.me/'.$username;
        }

        $id = (string) config('services.telegram.channel_id');
        if ($id !== '' && str_starts_with($id, '@')) {
            return 'https://t.me/'.ltrim($id, '@');
        }

        return null;
    }

    public function miniAppUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/discover';
    }

    public function sendMessage(int|string $chatId, string $text, array $extra = []): bool
    {
        $result = $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], $extra));

        return (bool) ($result['ok'] ?? false);
    }

    public function getChatMember(int|string $chatId, int|string $userId): ?array
    {
        $result = $this->request('getChatMember', [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        if (! ($result['ok'] ?? false)) {
            return null;
        }

        return $result['result'] ?? null;
    }

    public function isChannelMember(int|string $userId): bool
    {
        $channelId = config('services.telegram.channel_id');
        if (! filled($channelId)) {
            return true;
        }

        $member = $this->getChatMember($channelId, $userId);
        $status = $member['status'] ?? null;

        return in_array($status, ['creator', 'administrator', 'member', 'restricted'], true);
    }

    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => false,
        ];

        if (filled($secretToken)) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->request('setWebhook', $payload) ?? ['ok' => false];
    }

    public function request(string $method, array $payload = []): ?array
    {
        $token = config('services.telegram.bot_token');
        if (! filled($token)) {
            return null;
        }

        try {
            $response = Http::timeout(20)
                ->asJson()
                ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

            $json = $response->json();
            if (! is_array($json)) {
                return null;
            }

            if (! ($json['ok'] ?? false)) {
                Log::warning('Telegram API error', [
                    'method' => $method,
                    'description' => $json['description'] ?? null,
                ]);
            }

            return $json;
        } catch (\Throwable $e) {
            Log::warning('Telegram API request failed', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
