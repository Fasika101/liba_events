<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Super Admin</h1>
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
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['companies'] ?? 0 }}</h3>
                    <p>Companies</p>
                </div>
                <div class="icon"><i class="fas fa-building"></i></div>
                <a href="{{ route('super-admin.companies.index') }}" class="small-box-footer">
                    Manage <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['admins'] ?? 0 }}</h3>
                    <p>Company admins</p>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
                <a href="{{ route('super-admin.company-admins.create') }}" class="small-box-footer">
                    Add admin <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['agents'] ?? 0 }}</h3>
                    <p>Agents (all companies)</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <span class="small-box-footer text-white-50" style="cursor:default;">
                    Sales agents across tenants
                </span>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4 col-sm-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>{{ number_format($smsSummary['sms_sent_count']) }}</h3>
                    <p>SMS sent (all orgs)</p>
                </div>
                <div class="icon"><i class="fas fa-sms"></i></div>
                <a href="{{ route('super-admin.accounting.index') }}" class="small-box-footer">
                    Accounting <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="small-box bg-olive">
                <div class="inner">
                    <h3>{{ number_format($smsSummary['total_revenue_etb'], 0) }}</h3>
                    <p>SMS revenue (ETB)</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
                <a href="{{ route('super-admin.accounting.index') }}" class="small-box-footer">
                    View breakdown <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>{{ number_format($smsSummary['sender_id_count']) }}</h3>
                    <p>Sender IDs sold</p>
                </div>
                <div class="icon"><i class="fas fa-id-badge"></i></div>
                <span class="small-box-footer">{{ number_format($smsSummary['sender_id_revenue_etb'], 0) }} ETB from sender IDs</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Recent companies</h3>
                    <div class="card-tools">
                        <a href="{{ route('super-admin.companies.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> New company
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th>Users</th>
                                <th>Events</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCompanies as $c)
                                <tr>
                                    <td class="font-weight-bold">
                                        <a href="{{ route('super-admin.companies.show', $c) }}">{{ $c->name }}</a>
                                    </td>
                                    <td>{{ $c->users_count }}</td>
                                    <td>{{ $c->events_count }}</td>
                                    <td class="text-muted">{{ $c->created_at?->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No companies yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
