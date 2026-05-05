<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing('event', 'agent');
    }

    public function envelope(): Envelope
    {
        $eventTitle = $this->ticket->event?->title ?? 'Event';

        return new Envelope(
            subject: sprintf('Your ticket — %s [%s]', $eventTitle, $this->ticket->ticket_code),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-receipt',
        );
    }
}
