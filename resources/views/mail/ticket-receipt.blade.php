@php
    $ev = $ticket->event;
    $primary = $ev ? ($ev->end_at ?? $ev->start_at) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.5; color: #1d2d44; max-width: 560px; margin: 0 auto; padding: 24px; }
        .muted { color: #6b7280; font-size: 13px; }
        .code { font-family: 'Courier New', monospace; font-size: 20px; font-weight: 700; letter-spacing: 0.08em; color: #1a3c6e; margin: 12px 0; }
        .banner { background: #f3f4f6; border-radius: 8px; padding: 14px 16px; margin: 16px 0; }
    </style>
</head>
<body>
    <p>Hello {{ $ticket->buyer_name }},</p>

    <p>This email confirms your ticket purchase for <strong>{{ $ev?->title ?? 'your event' }}</strong>.</p>

    <div class="banner">
        <div class="muted" style="margin-bottom:4px;">Ticket code — present this at pickup</div>
        <div class="code">{{ $ticket->ticket_code }}</div>
        @if ($primary)
            <p class="muted" style="margin:8px 0 0;">
                <strong>Date on ticket:</strong> {{ \App\Helpers\EthiopianCalendar::format($primary) }}
            </p>
        @endif
        <p class="muted" style="margin:8px 0 0;">
            <strong>Amount paid:</strong> {{ number_format($ticket->price_paid, 2) }} {{ $ticket->currency }}
        </p>
    </div>

    <p class="muted">
        Please arrive early with a valid ID and this code (printed or on your phone).
        @if ($ticket->agent)
            Sold by {{ $ticket->agent->name }}.
        @endif
    </p>

    <p class="muted">
        — {{ config('app.name') }}
    </p>
</body>
</html>
