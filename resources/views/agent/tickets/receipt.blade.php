<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket Receipt — {{ $ticket->ticket_code }}</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">

    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Ticket card ── */
        .ticket-wrap {
            max-width: 680px;
            margin: 2rem auto;
            width: 100%;
        }

        .ticket {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,.14);
            overflow: hidden;
        }

        /* Coloured header band */
        .ticket-header {
            background: linear-gradient(135deg, #1a3c6e 0%, #2563a8 100%);
            color: #fff;
            padding: 1.5rem 2rem 1rem;
        }
        .ticket-header .org-name {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .ticket-header .event-name {
            font-size: 1.1rem;
            font-weight: 600;
            opacity: .9;
            margin-top: .2rem;
        }

        /* Event photo strip */
        .ticket-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }

        /* Dashed tear line */
        .tear-line {
            border: none;
            border-top: 3px dashed #d1d8e2;
            margin: 0;
        }

        /* Main body */
        .ticket-body {
            padding: 1.5rem 2rem;
        }

        /* PAID stamp */
        .paid-stamp {
            display: inline-block;
            border: 3px solid #28a745;
            color: #28a745;
            border-radius: 6px;
            padding: .1rem .7rem;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: .12em;
            transform: rotate(-3deg);
            text-transform: uppercase;
        }

        /* Code block */
        .ticket-code {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: .18em;
            color: #1a3c6e;
            word-break: break-all;
        }

        /* Info rows */
        .info-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8592a3;
            margin-bottom: .1rem;
        }
        .info-value {
            font-weight: 600;
            color: #1d2d44;
        }

        /* Pickup notice */
        .pickup-notice {
            background: #fff8e1;
            border-left: 4px solid #f6c23e;
            border-radius: 0 6px 6px 0;
            padding: .9rem 1.2rem;
            font-size: .92rem;
        }

        /* QR section */
        #qr-code canvas, #qr-code img { border-radius: 8px; }

        /* Footer */
        .ticket-footer {
            background: #f8f9fb;
            border-top: 1px solid #e9ecef;
            padding: .9rem 2rem;
            font-size: .8rem;
            color: #8592a3;
        }

        /* ── Action bar (not printed) ── */
        .action-bar {
            max-width: 680px;
            margin: 0 auto 2rem;
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* ── Print styles ── */
        @media print {
            body { background: #fff; }
            .action-bar, .no-print { display: none !important; }
            .ticket { box-shadow: none; border-radius: 0; }
            .ticket-wrap { margin: 0; max-width: 100%; }
            @page { margin: .5cm; }
        }

        @media (max-width: 575px) {
            .ticket-body { padding: 1rem 1.2rem; }
            .ticket-header { padding: 1.2rem 1.2rem .8rem; }
            .ticket-code { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

    {{-- Action buttons --}}
    <div class="action-bar px-3 no-print">
        <a href="{{ route('agent.tickets.create') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Sell Another Ticket
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print mr-1"></i> Print Receipt
        </button>
        <button onclick="downloadReceipt()" class="btn btn-success">
            <i class="fas fa-download mr-1"></i> Save as Image
        </button>
        <a href="{{ route('agent.dashboard') }}" class="btn btn-outline-secondary ml-auto">
            <i class="fas fa-home mr-1"></i> Dashboard
        </a>
    </div>

    {{-- Ticket --}}
    <div class="ticket-wrap px-3" id="receipt">
        <div class="ticket">

            {{-- Header --}}
            <div class="ticket-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="org-name">
                            <i class="fas fa-ticket-alt mr-2" style="opacity:.8;"></i>
                            {{ config('app.name', 'Liba Events') }}
                        </div>
                        <div class="event-name">{{ $ticket->event->title ?? 'Event Ticket' }}</div>
                    </div>
                    <div class="paid-stamp ml-3 mt-1">PAID</div>
                </div>
            </div>

            {{-- Event photo --}}
            @if ($ticket->event && $ticket->event->photo_path)
                <img src="{{ asset('storage/' . $ticket->event->photo_path) }}"
                     alt="{{ $ticket->event->title }}"
                     class="ticket-photo">
            @endif

            <div class="ticket-body">

                {{-- Ticket code + QR --}}
                <div class="row align-items-center mb-4">
                    <div class="col">
                        <div class="info-label">Ticket Code</div>
                        <div class="ticket-code">{{ $ticket->ticket_code }}</div>
                    </div>
                    <div class="col-auto text-center">
                        <div id="qr-code"></div>
                        <div class="text-muted mt-1" style="font-size:.7rem;">Scan to verify</div>
                    </div>
                </div>

                <hr class="tear-line mb-4">

                {{-- Event info --}}
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3">
                        <div class="info-label"><i class="fas fa-calendar mr-1"></i> Date on ticket</div>
                        <div class="info-value">
                            @if ($ticket->event)
                                @php
                                    $ev = $ticket->event;
                                    $primary = $ev->end_at ?? $ev->start_at;
                                @endphp
                                @if ($primary)
                                    <strong class="d-block" style="font-size:1.1rem;letter-spacing:.01em;">
                                        {{ \App\Helpers\EthiopianCalendar::format($primary) }}
                                    </strong>
                                    @if ($ev->end_at && $ev->start_at && $ev->end_at->ne($ev->start_at))
                                        <span class="text-muted small d-block mt-1">
                                            Ticket sales from {{ \App\Helpers\EthiopianCalendar::format($ev->start_at) }}
                                        </span>
                                    @endif
                                @else
                                    —
                                @endif
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="info-label"><i class="fas fa-money-bill-wave mr-1"></i> Amount Paid</div>
                        <div class="info-value text-success" style="font-size:1.2rem;">
                            {{ number_format($ticket->price_paid, 2) }} {{ $ticket->currency }}
                        </div>
                    </div>
                </div>

                <hr class="tear-line mb-4">

                {{-- Buyer info (occupation is stored for admin/customers only — not shown on ticket) --}}
                <div class="info-label mb-2"><i class="fas fa-user mr-1"></i> Passenger / Buyer</div>
                <div class="row mb-3">
                    <div class="col-sm-4 mb-2">
                        <div class="info-label">Full Name</div>
                        <div class="info-value">{{ $ticket->buyer_name }}</div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="info-label">Email</div>
                        <div class="info-value" style="word-break:break-all;">
                            {{ $ticket->buyer_email ?: '—' }}
                        </div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="info-label">Phone</div>
                        <div class="info-value">{{ $ticket->buyer_phone ?: '—' }}</div>
                    </div>
                    @if($ticket->buyer_address)
                    <div class="col-12 mt-1">
                        <div class="info-label"><i class="fas fa-map-marker-alt mr-1"></i> Address</div>
                        <div class="info-value">{{ $ticket->buyer_address }}</div>
                    </div>
                    @endif
                </div>

                <hr class="tear-line mb-4">

                {{-- Pickup notice --}}
                <div class="pickup-notice mb-3">
                    <div class="font-weight-bold mb-1">
                        <i class="fas fa-map-marker-alt mr-2 text-warning"></i> Important — Pickup Information
                    </div>
                    <div>
                        Please <strong>arrive early</strong> at our pickup location and present this receipt
                        (printed or on your phone) along with a valid ID.
                        This ticket is your proof of payment.
                    </div>
                </div>

                {{-- Sale metadata --}}
                <div class="row text-muted" style="font-size:.8rem;">
                    <div class="col-sm-6 mb-1">
                        <i class="fas fa-clock mr-1"></i>
                        Issued: {{ optional($ticket->sold_at)->format('M d, Y H:i') }}
                    </div>
                    <div class="col-sm-6 mb-1">
                        <i class="fas fa-user-tie mr-1"></i>
                        Agent: {{ $ticket->agent->name ?? '—' }}
                    </div>
                </div>
            </div>

            <div class="ticket-footer d-flex justify-content-between flex-wrap">
                <span><i class="fas fa-shield-alt mr-1 text-success"></i> Authentic receipt — {{ config('app.name') }}</span>
                <span>Ref: <strong>{{ $ticket->ticket_code }}</strong></span>
            </div>
        </div>
    </div>

    <!-- jQuery + Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <!-- html2canvas for image download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        // ── QR encodes just the ticket code so door scanners can read it instantly ──
        new QRCode(document.getElementById('qr-code'), {
            text:           "{{ $ticket->ticket_code }}",
            width:          130,
            height:         130,
            colorDark:      '#1a3c6e',
            colorLight:     '#ffffff',
            correctLevel:   QRCode.CorrectLevel.M
        });

        // ── Download as PNG image ──
        function downloadReceipt() {
            var btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Preparing…';

            html2canvas(document.getElementById('receipt'), {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(function (canvas) {
                var link = document.createElement('a');
                link.download = 'ticket-{{ $ticket->ticket_code }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download mr-1"></i> Save as Image';
            });
        }
    </script>
</body>
</html>
