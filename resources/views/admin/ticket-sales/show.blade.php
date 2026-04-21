<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Buyers — {{ $event->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.ticket-sales.index') }}">Ticket Sales</a></li>
                    <li class="breadcrumb-item active">{{ $event->title }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
        </div>
    @endif

    {{-- Event summary banner --}}
    <div class="card card-outline card-primary mb-4">
        <div class="card-body p-0">
            <div class="row no-gutters align-items-center">

                {{-- Photo --}}
                <div class="col-auto">
                    @if($event->photo_path)
                        <img src="{{ asset('storage/'.$event->photo_path) }}"
                             alt="{{ $event->title }}"
                             style="width:110px;height:110px;object-fit:cover;border-radius:4px 0 0 4px;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light"
                             style="width:110px;height:110px;border-radius:4px 0 0 4px;">
                            <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                        </div>
                    @endif
                </div>

                {{-- Meta --}}
                <div class="col px-4 py-3">
                    <h4 class="font-weight-bold mb-1">{{ $event->title }}</h4>
                    <div class="text-muted small mb-2">
                        <i class="fas fa-calendar mr-1"></i>@ethdate($event->start_at)
                        &nbsp;·&nbsp;
                        <i class="fas fa-tag mr-1"></i>{{ $event->price }} {{ $event->currency }} per ticket
                        @if($event->capacity > 0)
                            &nbsp;·&nbsp;
                            <i class="fas fa-users mr-1"></i>Capacity: {{ $event->capacity }}
                        @endif
                    </div>
                    <div class="d-flex flex-wrap" style="gap:1rem;">
                        <div>
                            <span class="text-info font-weight-bold" style="font-size:1.3rem;">{{ $tickets->count() }}</span>
                            <span class="text-muted small ml-1">Tickets Sold</span>
                        </div>
                        <div>
                            <span class="text-success font-weight-bold" style="font-size:1.3rem;">
                                {{ number_format($totalRevenue, 2) }}
                            </span>
                            <span class="text-muted small ml-1">{{ $event->currency }} Revenue</span>
                        </div>
                        @if($event->capacity > 0)
                            <div>
                                <span class="text-secondary font-weight-bold" style="font-size:1.3rem;">
                                    {{ max(0, $event->capacity - $tickets->count()) }}
                                </span>
                                <span class="text-muted small ml-1">Spots Remaining</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="col-auto px-3 py-3 d-flex flex-column align-items-end" style="gap:.5rem;">
                    <a href="{{ route('admin.ticket-sales.export', $event) }}"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel mr-1"></i> Export to Excel
                    </a>
                    <a href="{{ route('admin.ticket-sales.index') }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Agent summary mini-cards --}}
    @if($agentSummary->isNotEmpty())
        <div class="row mb-3">
            @foreach($agentSummary as $stat)
                <div class="col-lg-3 col-md-4 col-6 mb-2">
                    <div class="info-box shadow-sm mb-0">
                        <span class="info-box-icon bg-secondary">
                            <span style="font-size:1.1rem;font-weight:700;">
                                {{ strtoupper(substr($stat['name'], 0, 1)) }}
                            </span>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text text-truncate" title="{{ $stat['name'] }}">
                                {{ $stat['name'] }}
                            </span>
                            <span class="info-box-number">
                                {{ $stat['count'] }}
                                <small class="text-muted" style="font-size:.7rem;">tickets</small>
                            </span>
                            <span class="progress-description text-success" style="font-size:.78rem;">
                                {{ number_format($stat['revenue'], 2) }} {{ $event->currency }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Buyers table --}}
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users mr-2"></i>
                All Buyers
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.ticket-sales.export', $event) }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel mr-1"></i> Export to Excel
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            @if($tickets->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-ticket-alt fa-3x mb-3 d-block"></i>
                    <strong>No tickets sold for this event yet.</strong>
                </div>
            @else
                <table class="table table-hover table-bordered mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Buyer Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Occupation</th>
                            <th>Yeneshaa Abat</th>
                            <th>Ticket Code</th>
                            <th>Sold By</th>
                            <th>Date Sold</th>
                            <th class="text-right">Amount Paid</th>
                            <th class="text-center" style="width:90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $i => $ticket)
                            <tr>
                                <td class="text-muted text-center">{{ $i + 1 }}</td>
                                <td class="font-weight-bold">{{ $ticket->buyer_name }}</td>
                                <td>
                                    @if($ticket->buyer_phone)
                                        <a href="tel:{{ $ticket->buyer_phone }}" class="text-dark">
                                            <i class="fas fa-phone-alt mr-1 text-success" style="font-size:.75rem;"></i>
                                            {{ $ticket->buyer_phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->buyer_email)
                                        <a href="mailto:{{ $ticket->buyer_email }}" class="text-muted">
                                            {{ $ticket->buyer_email }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $ticket->buyer_address ?: '—' }}</td>
                                <td>{{ $ticket->buyer_occupation ?: '—' }}</td>
                                <td>{{ $ticket->yeneshaa_abat ? 'Yes' : 'No' }}</td>
                                <td><code>{{ $ticket->ticket_code }}</code></td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ $ticket->agent->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-muted">@ethdate($ticket->sold_at)</td>
                                <td class="text-right font-weight-bold text-success">
                                    {{ number_format($ticket->price_paid, 2) }} {{ $ticket->currency }}
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('admin.ticket-sales.tickets.edit', [$event, $ticket]) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       title="Edit this ticket">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="10" class="text-right font-weight-bold">Total Revenue</td>
                            <td class="text-right font-weight-bold text-success" style="font-size:1.05rem;">
                                {{ number_format($totalRevenue, 2) }} {{ $event->currency }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
