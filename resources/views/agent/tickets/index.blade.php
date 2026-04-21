<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">My Tickets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('agent.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Tickets</li>
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

    {{-- Event filter + sell --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <div class="form-inline flex-wrap align-items-center">
                <div class="input-group mr-2 mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                    </div>
                    <select id="eventFilter" class="form-control" style="min-width:220px;">
                        <option value="">All Events</option>
                        @foreach ($agentEvents as $ev)
                            <option value="{{ $ev->id }}" @selected((string)($selectedEventId ?? '') === (string)$ev->id)>
                                {{ $ev->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2 mr-2">
                    <button type="button" id="clearEventFilter" class="btn btn-outline-secondary" style="display:none;">
                        <i class="fas fa-times mr-1"></i> Clear filter
                    </button>
                </div>
                <div class="ml-auto mb-2">
                    <a href="{{ route('agent.tickets.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Sell a Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-ticket-alt mr-2"></i> Your ticket sales
            </h3>
        </div>
        <div class="card-body table-responsive p-0 pt-2 px-2 pb-2 agent-tickets-dt-card">
            <table id="agentTicketsTable" class="table table-hover table-sm table-bordered mb-0 w-100" style="width:100%!important;">
                <thead class="thead-light">
                    <tr>
                        <th>Event</th>
                        <th>Buyer</th>
                        <th>Phone</th>
                        <th>Price Paid</th>
                        <th>Date Sold</th>
                        <th>Ticket Code</th>
                        <th class="text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
    <style>
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_length select {
            display: inline-block;
            width: auto;
            min-width: 4.5rem;
            padding: 0.25rem 1.5rem 0.25rem 0.5rem;
            height: calc(1.8125rem + 2px);
            font-size: 0.875rem;
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_filter input {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            height: calc(1.8125rem + 2px);
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_info {
            font-size: 0.875rem;
            padding-top: 0.65rem;
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_paginate {
            padding-top: 0.35rem;
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem !important;
            margin-left: 2px;
            font-size: 0.8125rem !important;
            line-height: 1.3;
            border-radius: 0.2rem !important;
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--accent, #4f6ef7) !important;
            color: #fff !important;
            border: 1px solid var(--accent, #4f6ef7) !important;
        }
        .agent-tickets-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.45;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <script>
    (function () {
        var dataUrl = @json(route('agent.tickets.data'));
        var createUrl = @json(route('agent.tickets.create'));
        var $filter = $('#eventFilter');
        var $clearBtn = $('#clearEventFilter');

        function updateClearVisibility() {
            if ($filter.val()) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }
        }

        var table = $('#agentTicketsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
                data: function (d) {
                    d.event_id = $filter.val() || '';
                },
                error: function (xhr) {
                    if (xhr.status === 401 || xhr.status === 403) window.location.reload();
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
            order: [[4, 'desc']],
            autoWidth: false,
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ tickets',
                info: 'Showing _START_ to _END_ of _TOTAL_ tickets',
                infoEmpty: 'No tickets to show',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching tickets — try another search or clear the event filter.',
                emptyTable: 'No tickets yet. Use “Sell a Ticket” above when you are ready.',
                processing: '<span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Loading…</span>'
            },
            columnDefs: [
                { orderable: false, targets: 6 },
                { orderable: true, targets: [0, 1, 2, 3, 4, 5] },
                { className: 'align-middle', targets: [0, 1, 2, 3, 4, 5] },
                { className: 'align-middle text-center', targets: [6] }
            ],
            dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        $filter.on('change', function () {
            updateClearVisibility();
            table.ajax.reload();
        });

        $clearBtn.on('click', function () {
            $filter.val('');
            updateClearVisibility();
            table.ajax.reload();
        });

        updateClearVisibility();
    })();
    </script>
    @endpush
</x-app-layout>
