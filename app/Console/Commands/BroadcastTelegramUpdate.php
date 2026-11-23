<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class BroadcastTelegramUpdate extends Command
{
    protected $signature = 'telegram:broadcast {message : English message} {--am= : Optional Amharic message}';

    protected $description = 'Send an update to all registered marketplace users via the bot';

    public function handle(TelegramNotifier $notifier): int
    {
        $sent = $notifier->broadcast(
            (string) $this->argument('message'),
            $this->option('am')
        );

        $this->info("Sent to {$sent} users.");

        return 0;
    }
}
