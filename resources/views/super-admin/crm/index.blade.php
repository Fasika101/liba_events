<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">CRM — All companies</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">CRM</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row mb-3">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>{{ $totalUnique }}</h3>
                    <p>Unique customers (per company &amp; phone)</p>
                </div>
                <div class="icon"><i class="fas fa-address-book"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-success">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h3 class="card-title mb-2 mb-md-0">
                <i class="fas fa-users mr-2"></i> Customers across all organizations
            </h3>
            <div class="d-flex flex-wrap align-items-center" style="gap:.5rem;">
                <a href="{{ route('super-admin.crm.export') }}" id="crmExportBtn"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0 pt-2 px-2 pb-2 crm-dt-card">
            <table id="crmTable" class="table table-hover table-sm table-bordered mb-0 w-100" style="width:100%!important;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Occupation</th>
                        <th>Yeneshaa Abat</th>
                        <th>Company</th>
                        <th>Last Event</th>
                        <th>Last Event Attended</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
    <style>
        .crm-dt-card .dataTables_wrapper .dataTables_length select {
            display: inline-block;
            width: auto;
            min-width: 4.5rem;
            padding: 0.25rem 1.5rem 0.25rem 0.5rem;
            height: calc(1.8125rem + 2px);
            font-size: 0.875rem;
        }
        .crm-dt-card .dataTables_wrapper .dataTables_filter input {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            height: calc(1.8125rem + 2px);
        }
        .crm-dt-card .dataTables_wrapper .dataTables_info {
            font-size: 0.875rem;
            padding-top: 0.65rem;
        }
        .crm-dt-card .dataTables_wrapper .dataTables_paginate {
            padding-top: 0.35rem;
        }
        .crm-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem !important;
            margin-left: 2px;
            font-size: 0.8125rem !important;
            line-height: 1.3;
            border-radius: 0.2rem !important;
        }
        .crm-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .crm-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--accent, #4f6ef7) !important;
            color: #fff !important;
            border: 1px solid var(--accent, #4f6ef7) !important;
        }
        .crm-dt-card .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.45;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
    <script>
    (function () {
        var exportBaseUrl = @json(route('super-admin.crm.export'));
        var dataUrl = @json(route('super-admin.crm.data'));
        var initialSearch = @json($initialSearch ?? '');

        var table = $('#crmTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
                error: function (xhr) {
                    if (xhr.status === 401 || xhr.status === 403) window.location.reload();
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
            order: [[9, 'desc']],
            autoWidth: false,
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ rows',
                info: 'Showing _START_ to _END_ of _TOTAL_ customers',
                infoEmpty: 'No customers to show',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching customers',
                processing: '<span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i>Loading…</span>'
            },
            columnDefs: [
                { orderable: true, targets: '_all' },
                { className: 'align-middle', targets: '_all' }
            ],
            dom: '<"row align-items-center mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row align-items-center mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        if (initialSearch) {
            table.search(initialSearch).draw();
        }

        $('#crmExportBtn').on('click', function (e) {
            e.preventDefault();
            var q = table.search();
            window.location.href = exportBaseUrl + (q ? ('?search=' + encodeURIComponent(q)) : '');
        });
    })();
    </script>
    @endpush
</x-app-layout>
