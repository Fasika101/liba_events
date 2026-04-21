<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Ticket Sales</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Ticket Sales</li>
                </ol>
            </div>
        </div>
    </x-slot>

    {{-- Grand totals --}}
    <div class="row mb-4">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalTickets }}</h3>
                    <p>Total Tickets Sold</p>
                </div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($totalRevenue, 2) }}</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $events->count() }}</h3>
                    <p>Events</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
    </div>

    {{-- Per-event cards --}}
    @forelse ($events as $event)
        @php
            $pct = ($event->capacity > 0)
                ? min(100, round($event->tickets_count / $event->capacity * 100))
                : null;
            $barColor = $pct === null ? 'bg-primary'
                      : ($pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success'));
            $isFull   = $event->capacity > 0 && $event->tickets_count >= $event->capacity;
        @endphp

        <div class="card card-outline card-primary mb-3">
            <div class="card-header p-0">
                <div class="row no-gutters align-items-stretch">

                    {{-- Event photo --}}
                    <div class="col-auto">
                        @if($event->photo_path)
                            <img src="{{ asset('storage/'.$event->photo_path) }}"
                                 alt="{{ $event->title }}"
                                 style="width:90px;height:90px;object-fit:cover;border-radius:4px 0 0 0;">
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light"
                                 style="width:90px;height:90px;border-radius:4px 0 0 0;">
                                <i class="fas fa-calendar-alt fa-2x text-muted"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Event title + meta --}}
                    <div class="col d-flex align-items-center px-3 py-2">
                        <div>
                            <h5 class="mb-1 font-weight-bold">
                                {{ $event->title }}
                                @if($isFull)
                                    <span class="badge badge-danger ml-1">FULL</span>
                                @endif
                            </h5>
                            <div class="text-muted small">
                                <i class="fas fa-calendar mr-1"></i>@ethdate($event->start_at)
                                &nbsp;·&nbsp;
                                <i class="fas fa-tag mr-1"></i>{{ $event->price }} {{ $event->currency }}
                                @if($event->status !== 'active')
                                    &nbsp;·&nbsp;
                                    <span class="badge badge-{{ $event->status === 'draft' ? 'secondary' : 'dark' }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stats pills --}}
                    <div class="col-auto d-flex align-items-center px-3 py-2" style="gap:.75rem;flex-wrap:wrap;">
                        <div class="text-center px-2">
                            <div class="font-weight-bold text-info" style="font-size:1.5rem;line-height:1;">
                                {{ $event->tickets_count }}
                            </div>
                            <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Tickets Sold</div>
                        </div>
                        @if($event->capacity > 0)
                            <div class="text-center px-2">
                                <div class="font-weight-bold text-secondary" style="font-size:1.5rem;line-height:1;">
                                    {{ max(0, $event->capacity - $event->tickets_count) }}
                                </div>
                                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Remaining</div>
                            </div>
                        @endif
                        <div class="text-center px-2">
                            <div class="font-weight-bold text-success" style="font-size:1.5rem;line-height:1;">
                                {{ number_format($event->tickets_sum_price_paid ?? 0, 0) }}
                            </div>
                            <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Revenue ({{ $event->currency }})</div>
                        </div>
                    </div>
                </div>

                {{-- Capacity progress bar --}}
                @if($pct !== null)
                    <div class="progress" style="height:6px;border-radius:0;">
                        <div class="progress-bar {{ $barColor }}"
                             role="progressbar"
                             style="width:{{ $pct }}%"
                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="px-3 py-1 bg-light text-muted" style="font-size:.75rem;">
                        <i class="fas fa-users mr-1"></i>
                        Capacity: <strong>{{ $event->tickets_count }} / {{ $event->capacity }}</strong>
                        ({{ $pct }}% full)
                    </div>
                @endif
            </div>

            {{-- Footer: link to buyer detail page --}}
            @if($event->tickets_count > 0)
                <div class="card-footer py-2 px-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="fas fa-users mr-1"></i>
                        {{ $event->tickets_count }} ticket{{ $event->tickets_count !== 1 ? 's' : '' }} sold
                    </span>
                    <a href="{{ route('admin.ticket-sales.show', $event) }}"
                       class="btn btn-info btn-sm">
                        <i class="fas fa-list mr-1"></i> View Buyers
                    </a>
                </div>
            @else
                <div class="card-footer py-2 px-3">
                    <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>No tickets sold yet.</span>
                </div>
            @endif
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                <strong>No events yet.</strong><br>
                <a href="{{ route('admin.events.create') }}" class="btn btn-primary mt-2">
                    <i class="fas fa-plus-circle mr-1"></i> Create your first event
                </a>
            </div>
        </div>
    @endforelse
</x-app-layout>
