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

    <style>
        .ts-summary .small-box { margin-bottom: 1rem; }
        .ts-table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            border-top: 0;
            white-space: nowrap;
            vertical-align: middle;
        }
        .ts-table tbody td { vertical-align: middle; }
        .ts-event {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }
        .ts-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f1f5f9;
        }
        .ts-thumb-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }
        .ts-title {
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 2px;
        }
        .ts-meta {
            font-size: 0.78rem;
            color: #64748b;
        }
        .ts-metric {
            font-weight: 700;
            font-size: 1.05rem;
            line-height: 1.1;
            color: #0f172a;
        }
        .ts-metric-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }
        .ts-progress {
            height: 6px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            min-width: 72px;
            max-width: 120px;
        }
        .ts-progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }
        .ts-actions .btn {
            white-space: nowrap;
        }
        @media (max-width: 767.98px) {
            .ts-table thead { display: none; }
            .ts-table,
            .ts-table tbody,
            .ts-table tr,
            .ts-table td {
                display: block;
                width: 100%;
            }
            .ts-table tr {
                border-bottom: 1px solid #e2e8f0;
                padding: 14px 12px;
            }
            .ts-table td {
                border: 0 !important;
                padding: 4px 0 !important;
            }
            .ts-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 0.68rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #94a3b8;
                margin-bottom: 2px;
            }
            .ts-actions {
                margin-top: 8px;
            }
            .ts-actions .btn {
                width: 100%;
                min-height: 44px;
            }
        }
    </style>

    {{-- Grand totals --}}
    <div class="row ts-summary mb-3">
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

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-chart-line mr-2"></i>Sales by event</h3>
            <div class="card-tools">
                <span class="text-muted small">Latest events first</span>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 ts-table">
                <thead class="thead-light">
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Sold</th>
                        <th>Remaining</th>
                        <th>Capacity</th>
                        <th>Revenue</th>
                        <th class="text-right">Buyers</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        @php
                            $sold = (int) $event->tickets_count;
                            $revenue = (float) ($event->tickets_sum_price_paid ?? 0);
                            $remaining = $event->capacity > 0 ? max(0, $event->capacity - $sold) : null;
                            $pct = $event->capacity > 0
                                ? min(100, round($sold / $event->capacity * 100))
                                : null;
                            $barColor = $pct === null ? 'bg-primary'
                                      : ($pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success'));
                            $isFull = $event->capacity > 0 && $sold >= $event->capacity;
                        @endphp
                        <tr>
                            <td data-label="Event">
                                <div class="ts-event">
                                    @if ($event->photo_path)
                                        <img src="{{ asset('storage/'.$event->photo_path) }}"
                                             alt="{{ $event->title }}"
                                             class="ts-thumb">
                                    @else
                                        <div class="ts-thumb ts-thumb-fallback">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                    @endif
                                    <div style="min-width:0;">
                                        <div class="ts-title">
                                            {{ $event->title }}
                                            @if ($isFull)
                                                <span class="badge badge-danger">FULL</span>
                                            @endif
                                            @if ($event->trashed())
                                                <span class="badge badge-secondary">Archived</span>
                                            @endif
                                        </div>
                                        <div class="ts-meta">
                                            {{ number_format($event->price, 2) }} {{ $event->currency }}
                                            @if ($loop->first)
                                                <span class="badge badge-info ml-1">Latest</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Date">
                                <div class="ts-meta">
                                    <i class="fas fa-calendar-alt mr-1"></i>@ethdate($event->start_at)
                                </div>
                            </td>
                            <td data-label="Status">
                                @if ($event->trashed())
                                    <span class="badge badge-secondary">Archived</span>
                                @elseif ($event->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif ($event->status === 'draft')
                                    <span class="badge badge-secondary">Draft</span>
                                @else
                                    <span class="badge badge-dark">{{ ucfirst($event->status) }}</span>
                                @endif
                            </td>
                            <td data-label="Sold">
                                <div class="ts-metric text-info">{{ $sold }}</div>
                                <div class="ts-metric-label">Tickets</div>
                            </td>
                            <td data-label="Remaining">
                                @if ($remaining !== null)
                                    <div class="ts-metric text-secondary">{{ $remaining }}</div>
                                    <div class="ts-metric-label">Left</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Capacity">
                                @if ($pct !== null)
                                    <div class="ts-progress mb-1" title="{{ $pct }}% full">
                                        <span class="{{ $barColor }}" style="width:{{ $pct }}%;"></span>
                                    </div>
                                    <div class="ts-meta">{{ $sold }} / {{ $event->capacity }} ({{ $pct }}%)</div>
                                @else
                                    <span class="text-muted">Unlimited</span>
                                @endif
                            </td>
                            <td data-label="Revenue">
                                <div class="ts-metric text-success">{{ number_format($revenue, 0) }}</div>
                                <div class="ts-metric-label">{{ $event->currency }}</div>
                            </td>
                            <td class="text-right ts-actions" data-label="Buyers">
                                <a href="{{ route('admin.ticket-sales.show', $event) }}"
                                   class="btn btn-info btn-sm">
                                    <i class="fas fa-users mr-1"></i> View Buyers
                                    <span class="badge badge-light ml-1">{{ $sold }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                                <strong>No events yet.</strong><br>
                                <a href="{{ route('admin.events.create') }}" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus-circle mr-1"></i> Create your first event
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
