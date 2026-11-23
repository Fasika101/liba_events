<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\URL;

class TelegramNotifier
{
    public function __construct(private TelegramBotClient $bot)
    {
    }

    public function notifyOrderPaid(TicketOrder $order): void
    {
        $order->loadMissing(['attendee', 'event', 'ticket']);
        $attendee = $order->attendee;
        if (! $this->canNotify($attendee)) {
            return;
        }

        $locale = $attendee->locale ?: 'en';
        $eventTitle = $order->event?->title ?? 'Event';
        $code = $order->ticket?->ticket_code ?? '';
        $ticketUrl = $this->ticketUrl($order);

        $text = $locale === 'am'
            ? "✅ ትኬትዎ ተረጋግጧል!\n\n<b>{$eventTitle}</b>\nኮድ: <code>{$code}</code>\n\nትኬትዎን እዚህ ይመልከቱ:\n{$ticketUrl}"
            : "✅ Your ticket is confirmed!\n\n<b>{$eventTitle}</b>\nCode: <code>{$code}</code>\n\nOpen your ticket:\n{$ticketUrl}";

        $this->bot->sendMessage($attendee->telegram_id, $text, [
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => $locale === 'am' ? 'ትኬት ክፈት' : 'Open ticket', 'web_app' => ['url' => $this->bot->miniAppUrl().'/me']],
                ]],
            ],
        ]);
    }

    public function notifyOrderRejected(TicketOrder $order): void
    {
        $order->loadMissing(['attendee', 'event']);
        $attendee = $order->attendee;
        if (! $this->canNotify($attendee)) {
            return;
        }

        $locale = $attendee->locale ?: 'en';
        $eventTitle = $order->event?->title ?? 'Event';

        $text = $locale === 'am'
            ? "⚠️ ክፍያዎ አልተቀበለም።\n\n<b>{$eventTitle}</b>\nእባክዎ ዝርዝሮችዎን ያረጋግጡ ወይም ድርጅቱን ያነጋግሩ።"
            : "⚠️ Payment was not accepted.\n\n<b>{$eventTitle}</b>\nPlease check your details or contact the organizer.";

        $this->bot->sendMessage($attendee->telegram_id, $text);
    }

    public function notifyNewEvent(Event $event): int
    {
        $event->loadMissing('company');
        if (! $event->company || ! $event->company->isPremium() || $event->status !== 'active') {
            return 0;
        }

        $url = $this->bot->miniAppUrl().'/events/'.$event->id;
        $sent = 0;

        Attendee::query()
            ->whereNotNull('telegram_id')
            ->where('notifications_enabled', true)
            ->whereNotNull('profile_completed_at')
            ->orderBy('id')
            ->chunkById(100, function ($attendees) use ($event, $url, &$sent) {
                foreach ($attendees as $attendee) {
                    $locale = $attendee->locale ?: 'en';
                    $org = $event->company?->name ?? '';
                    $when = $event->start_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '';

                    $text = $locale === 'am'
                        ? "🎉 አዲስ ዝግጅት!\n\n<b>{$event->title}</b>\n{$org}\n{$when}\n\n{$url}"
                        : "🎉 New event!\n\n<b>{$event->title}</b>\n{$org}\n{$when}\n\n{$url}";

                    if ($this->bot->sendMessage($attendee->telegram_id, $text, [
                        'reply_markup' => [
                            'inline_keyboard' => [[
                                ['text' => $locale === 'am' ? 'ዝግጅት ክፈት' : 'View event', 'web_app' => ['url' => $url]],
                            ]],
                        ],
                    ])) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function broadcast(string $messageEn, ?string $messageAm = null): int
    {
        $sent = 0;

        Attendee::query()
            ->whereNotNull('telegram_id')
            ->where('notifications_enabled', true)
            ->whereNotNull('profile_completed_at')
            ->orderBy('id')
            ->chunkById(100, function ($attendees) use ($messageEn, $messageAm, &$sent) {
                foreach ($attendees as $attendee) {
                    $text = ($attendee->locale === 'am' && filled($messageAm)) ? $messageAm : $messageEn;
                    if ($this->bot->sendMessage($attendee->telegram_id, $text)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendWelcome(int|string $chatId, string $locale = 'en'): void
    {
        $channelUrl = $this->bot->channelUrl();
        $appUrl = $this->bot->miniAppUrl();

        $text = $locale === 'am'
            ? "እንኳን ደህና መጡ! 👋\n\nትኬቶችን ለመግዛት፣ መገለጫዎን ያዘጋጁ እና ቻናላችንን ይቀላቀሉ።\n\nከዚያ የሚኒ መተግበሪያውን ይክፈቱ።"
            : "Welcome! 👋\n\nTo buy tickets, set up your profile and join our channel.\n\nThen open the Mini App to discover events.";

        $rows = [
            [['text' => $locale === 'am' ? 'መተግበሪያ ክፈት' : 'Open App', 'web_app' => ['url' => $appUrl]]],
        ];

        if ($channelUrl) {
            $rows[] = [['text' => $locale === 'am' ? 'ቻናል ተቀላቀል' : 'Join channel', 'url' => $channelUrl]];
        }

        $this->bot->sendMessage($chatId, $text, [
            'reply_markup' => ['inline_keyboard' => $rows],
        ]);
    }

    private function canNotify(?Attendee $attendee): bool
    {
        return $attendee
            && $attendee->notifications_enabled
            && filled($attendee->telegram_id)
            && $this->bot->configured();
    }

    private function ticketUrl(TicketOrder $order): string
    {
        if ($order->ticket) {
            return URL::signedRoute('tickets.public-receipt', ['ticket' => $order->ticket->id], now()->addDays(30));
        }

        return $this->bot->miniAppUrl().'/orders/'.$order->public_code;
    }
}
