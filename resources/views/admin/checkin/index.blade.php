<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Check-In</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Check-In</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <style>
        .checkin-event-row {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 1rem 1rem;
        }
        .checkin-event-top {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            min-width: 0;
        }
        .checkin-event-info {
            flex: 1;
            min-width: 0;
        }
        .checkin-event-title {
            font-weight: 700;
            font-size: 1rem;
            line-height: 1.3;
            word-break: break-word;
        }
        .checkin-event-row .btn-open-checkin {
            width: 100%;
            min-height: 44px;
        }
        @media (min-width: 768px) {
            .checkin-event-row {
                flex-direction: row;
                align-items: center;
            }
            .checkin-event-row .btn-open-checkin {
                width: auto;
                white-space: nowrap;
            }
        }
    </style>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-qrcode mr-2"></i> Select an Event to Check In</h3>
        </div>
        <div class="card-body p-0">
            @forelse($events as $event)
                @php
                    $total     = $event->tickets_count;
                    $checkedIn = $event->checked_in_count;
                    $pct       = $total > 0 ? round($checkedIn / $total * 100) : 0;
                    $shortTitle = \Illuminate\Support\Str::limit($event->title, 13, '…');
                @endphp

                <div class="checkin-event-row">
                    <div class="checkin-event-top">
                        <div class="flex-shrink-0">
                            @if($event->photo_path)
                                <img src="{{ asset('storage/'.$event->photo_path) }}"
                                     alt="{{ $event->title }}"
                                     style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                     style="width:56px;height:56px;border-radius:8px;">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                </div>
                            @endif
                        </div>

                        <div class="checkin-event-info">
                            <div class="checkin-event-title" title="{{ $event->title }}">{{ $shortTitle }}</div>
                            <div class="text-muted small">
                                <i class="fas fa-calendar mr-1"></i>@ethdate($event->start_at)
                            </div>
                            <div class="mt-1" style="max-width:260px;">
                                <div class="progress" style="height:6px;border-radius:3px;">
                                    <div class="progress-bar bg-success"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;margin-top:2px;">
                                    {{ $checkedIn }} / {{ $total }} checked in
                                    @if($total > 0) ({{ $pct }}%) @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route($showRouteName, $event) }}"
                       class="btn btn-primary btn-open-checkin">
                        <i class="fas fa-qrcode mr-1"></i> Open Check-In
                    </a>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                    <strong>No active events found.</strong><br>
                    <a href="{{ route('admin.events.create') }}" class="btn btn-primary mt-2">
                        Create an Event
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
