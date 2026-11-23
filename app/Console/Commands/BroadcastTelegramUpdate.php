<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class BroadcastTelegramUpdate extends Command
{
    protected $signature = 'telegram:broadcast
        {message : English message}
        {--am= : Optional Amharic message}
        {--channel : Also post to the configured Telegram channel}
        {--channel-only : Post to the channel only (skip user DMs)}';

    protected $description = 'Send an update to registered users and/or the Telegram channel';

    public function handle(TelegramNotifier $notifier): int
    {
        $channelOnly = (bool) $this->option('channel-only');
        $toUsers = ! $channelOnly;
        $toChannel = $channelOnly || (bool) $this->option('channel');

        $result = $notifier->broadcast(
            (string) $this->argument('message'),
            $this->option('am'),
            $toUsers,
            $toChannel,
        );

        if ($toUsers) {
            $this->info("Users: {$result['users_sent']} / {$result['users_eligible']} sent.");
        }

        if ($toChannel) {
            if ($result['channel_posted']) {
                $this->info('Channel: posted.');
            } else {
                $this->error('Channel: '.($result['channel_error'] ?: 'failed'));
            }
        }

        return ($toChannel && ! $result['channel_posted']) ? 1 : 0;
    }
}
