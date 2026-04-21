<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">My Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['tickets'] ?? 0 }}</h3>
                    <p>Tickets Sold</p>
                </div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                <a href="{{ route('agent.tickets.index') }}" class="small-box-footer">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($stats['revenue'] ?? 0, 2) }}</h3>
                    <p>My Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('agent.tickets.index') }}" class="small-box-footer">
                    View Tickets <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $stats['events'] ?? 0 }}</h3>
                    <p>Available Events</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <a href="{{ route('agent.tickets.create') }}" class="small-box-footer">
                    Sell a Ticket <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Active Events — Quick Sell</h3>
                    <div class="card-tools">
                        <a href="{{ route('agent.tickets.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle mr-1"></i> Full Sell Form
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($events->isEmpty())
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                            No active events available right now.
                        </div>
                    @else
                        <div class="row">
                            @foreach ($events as $event)
                                @php
                                    $isFull    = $event->capacity > 0 && $event->tickets_count >= $event->capacity;
                                    $spotsLeft = $event->capacity > 0 ? max(0, $event->capacity - $event->tickets_count) : null;
                                @endphp
                                <div class="col-lg-4 col-md-6 col-12 mb-3">
                                    <div class="card h-100 border-0 shadow-sm {{ $isFull ? 'border border-danger' : '' }}"
                                         style="{{ $isFull ? 'opacity:.75;' : '' }}">

                                        {{-- Photo / placeholder with FULL banner --}}
                                        <div style="position:relative;">
                                            @if ($event->photo_path)
                                                <img src="{{ asset('storage/' . $event->photo_path) }}"
                                                     alt="{{ $event->title }}"
                                                     class="card-img-top"
                                                     style="height:140px;object-fit:cover;{{ $isFull ? 'filter:grayscale(60%)' : '' }}">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center bg-light"
                                                     style="height:140px;">
                                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                                </div>
                                            @endif

                                            @if ($isFull)
                                                <div style="position:absolute;top:0;left:0;right:0;bottom:0;
                                                            background:rgba(0,0,0,.45);display:flex;
                                                            align-items:center;justify-content:center;">
                                                    <span style="background:#dc3545;color:#fff;font-weight:800;
                                                                 font-size:1.1rem;letter-spacing:.12em;
                                                                 padding:.3rem 1rem;border-radius:4px;text-transform:uppercase;">
                                                        <i class="fas fa-ban mr-1"></i> FULL
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="card-body d-flex flex-column pb-3">
                                            <h6 class="card-title font-weight-bold mb-1">{{ $event->title }}</h6>
                                            <p class="card-text text-muted small mb-2">
                                                <i class="fas fa-calendar mr-1"></i>
                                                @ethdate($event->start_at)
                                                @if($event->end_at && $event->end_at->ne($event->start_at))
                                                    — @ethdate($event->end_at)
                                                @endif
                                            </p>
                                            <p class="card-text mb-2">
                                                <span class="badge badge-success" style="font-size:.85rem;">
                                                    {{ $event->price }} {{ $event->currency }}
                                                </span>
                                                @if ($event->capacity)
                                                    @if ($isFull)
                                                        <span class="badge badge-danger ml-1">
                                                            <i class="fas fa-ban mr-1"></i>Full ({{ $event->capacity }}/{{ $event->capacity }})
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary ml-1">
                                                            <i class="fas fa-users mr-1"></i>{{ $spotsLeft }} spot{{ $spotsLeft === 1 ? '' : 's' }} left
                                                        </span>
                                                    @endif
                                                @endif
                                            </p>

                                            @if ($event->description)
                                                @php($descId = 'desc-' . $event->id)
                                                <div class="mb-2">
                                                    <p class="card-text small text-muted mb-0 event-desc-preview"
                                                       id="{{ $descId }}-preview"
                                                       style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                        {{ $event->description }}
                                                    </p>
                                                    <div class="collapse" id="{{ $descId }}-full">
                                                        <p class="card-text small text-muted mb-0" style="white-space:pre-line;">{{ $event->description }}</p>
                                                    </div>
                                                    @if (strlen($event->description) > 120)
                                                        <a href="#" class="small font-weight-bold"
                                                           data-toggle="collapse"
                                                           data-target="#{{ $descId }}-full"
                                                           onclick="toggleDesc(this, '{{ $descId }}-preview'); return false;">
                                                            <i class="fas fa-chevron-down mr-1"></i>Read more
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($isFull)
                                                <button class="btn btn-secondary btn-sm mt-auto" disabled>
                                                    <i class="fas fa-ban mr-1"></i> Event Full — No Tickets Available
                                                </button>
                                            @else
                                                <a href="{{ route('agent.tickets.create', ['event_id' => $event->id]) }}"
                                                   class="btn btn-primary btn-sm mt-auto">
                                                    <i class="fas fa-ticket-alt mr-1"></i> Sell Ticket
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Recent Sales</h3>
                    <div class="card-tools">
                        <a href="{{ route('agent.tickets.index') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-list mr-1"></i> All Tickets
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Event</th>
                                <th>Buyer</th>
                                <th>Phone</th>
                                <th>Price</th>
                                <th>Date</th>
                                <th>Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTickets as $ticket)
                                <tr>
                                    <td class="font-weight-bold">{{ $ticket->event->title ?? '-' }}</td>
                                    <td>{{ $ticket->buyer_name }}</td>
                                    <td class="text-muted">{{ $ticket->buyer_phone ?: '—' }}</td>
                                    <td>
                                        <span class="badge badge-success">
                                            {{ $ticket->price_paid }} {{ $ticket->currency }}
                                        </span>
                                    </td>
                                    <td>@ethdate($ticket->sold_at)</td>
                                    <td><code class="text-muted">{{ $ticket->ticket_code }}</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-ticket-alt fa-2x mb-2 d-block"></i>
                                        No tickets sold yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleDesc(link, previewId) {
            var preview = document.getElementById(previewId);
            var isExpanding = link.getAttribute('aria-expanded') !== 'true';
            // Bootstrap toggles aria-expanded on the collapse target; we track it manually
            var collapsed = link.innerHTML.includes('Read more');
            if (collapsed) {
                preview.style.display = 'none';
                link.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Show less';
            } else {
                preview.style.display = '';
                link.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Read more';
            }
        }
    </script>
    @endpush
</x-app-layout>
