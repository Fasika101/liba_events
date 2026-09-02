<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class PublicTicketReceiptController extends Controller
{
    public function show(Request $request, Ticket $ticket)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This receipt link is invalid or has expired.');
        }

        $ticket->load('event', 'agent');

        return view('agent.tickets.receipt', compact('ticket'));
    }
}
