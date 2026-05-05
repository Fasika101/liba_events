<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Events</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Events</li>
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

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">All Events</h3>
            <div class="card-tools">
                <a href="{{ route('admin.events.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle mr-1"></i> New Event
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:60px;"></th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td class="py-1">
                                @if ($event->photo_path)
                                    <img src="{{ asset('storage/' . $event->photo_path) }}"
                                         alt="{{ $event->title }}"
                                         class="img-thumbnail"
                                         style="width:52px;height:52px;object-fit:cover;border-radius:4px;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded"
                                         style="width:52px;height:52px;">
                                        <i class="fas fa-image text-muted"></i>
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
                            <td class="align-middle">@ethdate($event->start_at)</td>
                            <td class="align-middle">{{ $event->price }} {{ $event->currency }}</td>
                            <td class="align-middle">
                                <span class="badge badge-{{ $event->status === 'active' ? 'success' : ($event->status === 'draft' ? 'secondary' : 'dark') }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </td>
                            <td class="align-middle">{{ $event->tickets_count }}</td>
                            <td class="align-middle">{{ number_format($event->tickets_sum_price_paid ?? 0, 2) }}</td>
                            <td class="text-right align-middle">
                                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('admin.events.destroy', $event) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger"
                                            onclick="return confirm('Archive this event? It will be hidden from the list. All ticket sales and buyer records will be kept.')">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3 d-block"></i>
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
        @if ($events->hasPages())
            <div class="card-footer clearfix">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    {{-- Event description modal --}}
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
                            <span class="text-muted small" id="eventModalDate"></span>
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
            $('#eventModalTitle').text(btn.data('title'));
            $('#eventModalDesc').text(btn.data('description'));
            $('#eventModalDate').html('<i class="fas fa-calendar mr-1"></i>' + btn.data('date'));
            $('#eventModalPrice').text(btn.data('price'));

            var photo = btn.data('photo');
            if (photo) {
                $('#eventModalImg').attr('src', photo).attr('alt', btn.data('title'));
                $('#eventModalImgWrap').show();
            } else {
                $('#eventModalImgWrap').hide();
            }

            // Wire edit link to the row's edit button href
            var editHref = btn.closest('tr').find('a[href*="/admin/events/"]').attr('href') || '#';
            $('#eventModalEdit').attr('href', editHref);
        });
    </script>
    @endpush
</x-app-layout>
