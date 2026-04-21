<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Admin Dashboard</h1>
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
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($summary['revenue'] ?? 0, 2) }}</h3>
                    <p>Total Revenue</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                    View Events <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['tickets'] ?? 0 }}</h3>
                    <p>Tickets Sold</p>
                </div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['events'] ?? 0 }}</h3>
                    <p>Active Events</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <a href="{{ route('admin.events.create') }}" class="small-box-footer">
                    Create Event <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Events Overview</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> New Event
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:46px;"></th>
                                <th>Title</th>
                                <th>Price</th>
                                <th>Date</th>
                                <th>Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td class="py-1">
                                        @if ($event->photo_path)
                                            <img src="{{ asset('storage/' . $event->photo_path) }}"
                                                 alt="{{ $event->title }}"
                                                 style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                                 style="width:40px;height:40px;">
                                                <i class="fas fa-image text-muted small"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="font-weight-bold align-middle">
                                        {{ $event->title }}
                                        @if ($event->description)
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 ml-1 text-info"
                                                    data-toggle="modal"
                                                    data-target="#eventModal"
                                                    data-title="{{ $event->title }}"
                                                    data-description="{{ $event->description }}"
                                                    data-date="@ethdate($event->start_at)"
                                                    data-price="{{ $event->price }} {{ $event->currency }}"
                                                    data-photo="{{ $event->photo_path ? asset('storage/'.$event->photo_path) : '' }}"
                                                    title="View description"
                                                    style="vertical-align:baseline;">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="align-middle"><span class="badge badge-light">{{ $event->price }} {{ $event->currency }}</span></td>
                                    <td class="align-middle">@ethdate($event->start_at)</td>
                                    <td class="align-middle"><span class="badge badge-info">{{ $event->tickets_count }}</span></td>
                                    <td class="align-middle text-success font-weight-bold">{{ number_format($event->tickets_sum_price_paid ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                        No events yet. <a href="{{ route('admin.events.create') }}">Create one</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Top Sales Agents</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.agents.index') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-users mr-1"></i> Manage
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Agent</th>
                                <th>Tickets</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agentStats as $agent)
                                <tr>
                                    <td>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white mr-2" style="width:28px;height:28px;font-size:.75rem;">
                                            {{ strtoupper(substr($agent->name, 0, 1)) }}
                                        </span>
                                        {{ $agent->name }}
                                    </td>
                                    <td><span class="badge badge-info">{{ $agent->tickets_count }}</span></td>
                                    <td class="text-success font-weight-bold">{{ number_format($agent->tickets_sum_price_paid ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                        No agents yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Shared event-description modal --}}
    <div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="eventModalTitle"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="eventModalImgWrap" style="display:none;">
                        <img id="eventModalImg" src="" alt=""
                             style="width:100%;max-height:280px;object-fit:cover;">
                    </div>
                    <div class="p-4">
                        <div class="d-flex align-items-center mb-3 flex-wrap" style="gap:.5rem;">
                            <span class="badge badge-success mr-2" id="eventModalPrice" style="font-size:.95rem;"></span>
                            <span class="text-muted small" id="eventModalDate">
                                <i class="fas fa-calendar mr-1"></i>
                            </span>
                        </div>
                        <hr class="mt-0">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size:.75rem;letter-spacing:.05em;">
                            Description
                        </h6>
                        <p id="eventModalDesc" class="mb-0" style="white-space:pre-line;line-height:1.7;"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="eventModalEdit" href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit mr-1"></i> Edit Event
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $('#eventModal').on('show.bs.modal', function (e) {
            var btn   = $(e.relatedTarget);
            var title = btn.data('title');
            var desc  = btn.data('description');
            var date  = btn.data('date');
            var price = btn.data('price');
            var photo = btn.data('photo');
            var eventId = btn.closest('tr').find('a[href*="/admin/events/"]').attr('href') || '#';

            $('#eventModalTitle').text(title);
            $('#eventModalDesc').text(desc);
            $('#eventModalDate').html('<i class="fas fa-calendar mr-1"></i>' + date);
            $('#eventModalPrice').text(price);

            var imgWrap = $('#eventModalImgWrap');
            if (photo) {
                $('#eventModalImg').attr('src', photo).attr('alt', title);
                imgWrap.show();
            } else {
                imgWrap.hide();
            }
        });
    </script>
    @endpush
</x-app-layout>
