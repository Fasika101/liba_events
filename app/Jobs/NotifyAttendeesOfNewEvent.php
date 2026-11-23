<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyAttendeesOfNewEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $eventId)
    {
    }

    public function handle(TelegramNotifier $notifier): void
    {
        $event = Event::query()->with('company')->find($this->eventId);
        if (! $event) {
            return;
        }

        $notifier->notifyNewEvent($event);
    }
}
