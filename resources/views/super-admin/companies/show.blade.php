<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0">Organization</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.companies.index') }}">Companies</a></li>
                    <li class="breadcrumb-item active">{{ $company->name }}</li>
                </ol>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
        </div>
    @endif

    @if ($company->is_suspended)
        <div class="alert alert-warning border-0 shadow-sm mb-3" style="border-radius:12px;">
            <i class="fas fa-ban mr-2"></i>
            <strong>Organization suspended.</strong> All admins and agents for this organization are blocked from signing in until you reactivate it.
        </div>
    @endif

    @push('styles')
    <style>
        .org-hero {
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 45%, #3730a3 100%);
            color: #fff;
            padding: 1.75rem 1.75rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.35);
            position: relative;
        }
        .org-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 100% 0%, rgba(79, 110, 247, 0.35), transparent 55%);
            pointer-events: none;
        }
        .org-hero-inner { position: relative; z-index: 1; }
        .org-hero h2 { font-weight: 800; letter-spacing: -0.03em; font-size: 1.5rem; margin-bottom: .35rem; }
        .org-hero .meta { color: rgba(255,255,255,.72); font-size: .82rem; }
        .org-hero .actions .btn { border-radius: 10px; font-weight: 600; font-size: .82rem; }
        .org-hero .btn-light {
            background: rgba(255,255,255,.95);
            border: none;
            color: #1e293b;
        }
        .org-hero .btn-light:hover { background: #fff; color: #0f172a; }
        .org-hero .btn-outline-light {
            border-color: rgba(255,255,255,.45);
            color: #fff;
        }
        .org-hero .btn-outline-light:hover {
            background: rgba(255,255,255,.12);
            border-color: rgba(255,255,255,.65);
            color: #fff;
        }
        .org-kpi {
            border-radius: 14px;
            border: 1px solid var(--border, #e8edf5);
            background: #fff;
            padding: 1.1rem 1.15rem;
            height: 100%;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .org-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,.1);
        }
        .org-kpi .icon-wrap {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            margin-bottom: .65rem;
        }
        .org-kpi .label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted, #64748b);
            margin-bottom: .2rem;
        }
        .org-kpi .value {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.03em;
            color: var(--text, #1e293b);
            line-height: 1.15;
        }
        .org-kpi .hint { font-size: .75rem; color: var(--muted, #64748b); margin-top: .35rem; }
        .org-card {
            border-radius: 14px;
            border: 1px solid var(--border, #e8edf5);
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
        }
        .org-card .card-header {
            background: linear-gradient(180deg, #fafbfd 0%, #fff 100%);
            border-bottom: 1px solid var(--border, #e8edf5);
            font-weight: 700;
            font-size: .95rem;
        }
        .org-table thead th {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted, #64748b);
            border-top: none;
        }
        .org-table tbody tr:hover { background: #f8faff; }
        .badge-soft {
            background: rgba(79, 110, 247, .12);
            color: #3730a3;
            font-weight: 600;
            font-size: .72rem;
            padding: .35rem .55rem;
            border-radius: 8px;
        }
    </style>
    @endpush

    <div class="org-hero">
        <div class="org-hero-inner d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div class="mb-3 mb-md-0">
                <h2 class="mb-1">{{ $company->name }}</h2>
                <div class="meta">
                    <i class="fas fa-fingerprint mr-1 opacity-75"></i> ID {{ $company->id }}
                    <span class="mx-2">·</span>
                    <i class="fas fa-user-shield mr-1 opacity-75"></i> {{ $adminsCount }} admin{{ $adminsCount === 1 ? '' : 's' }}
                    <span class="mx-2">·</span>
                    <i class="fas fa-clock mr-1 opacity-75"></i> Added {{ $company->created_at?->format('M j, Y') }}
                </div>
            </div>
            <div class="actions d-flex flex-wrap" style="gap:.5rem;">
                <a href="{{ route('super-admin.companies.edit', $company) }}" class="btn btn-light btn-sm">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <a href="{{ route('super-admin.company-admins.create', ['company_id' => $company->id]) }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-user-plus mr-1"></i> Add admin
                </a>
                <form method="POST" action="{{ route('super-admin.companies.suspension.toggle', $company) }}" class="d-inline">
                    @csrf
                    @if ($company->is_suspended)
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-unlock mr-1"></i> Reactivate organization
                        </button>
                    @else
                        <button type="submit" class="btn btn-warning btn-sm"
                                onclick="return confirm('Suspend this organization? All admins and agents will be unable to sign in until you reactivate.');">
                            <i class="fas fa-pause-circle mr-1"></i> Suspend organization
                        </button>
                    @endif
                </form>
                <form method="POST" action="{{ route('super-admin.companies.destroy', $company) }}"
                      class="d-inline"
                      onsubmit="return confirm('Delete this organization? All events, tickets, and user accounts for this tenant will be permanently removed. This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-light btn-sm border-danger text-white" style="border-color: rgba(248,113,113,.6)!important;">
                        <i class="fas fa-trash-alt mr-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="org-kpi">
                <div class="icon-wrap" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="label">Events</div>
                <div class="value">{{ number_format($eventsCount) }}</div>
                <div class="hint">Total events created for this organization</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="org-kpi">
                <div class="icon-wrap" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8;">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="label">Tickets sold</div>
                <div class="value">{{ number_format($ticketsSold) }}</div>
                <div class="hint">Across all events</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
            <div class="org-kpi">
                <div class="icon-wrap" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="label">Revenue</div>
                <div class="value">{{ number_format($revenue, 2) }}</div>
                <div class="hint">Sum of ticket prices sold</div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="org-kpi">
                <div class="icon-wrap" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309;">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="label">Agents</div>
                <div class="value">{{ number_format($agents->count()) }}</div>
                <div class="hint">Active sales accounts</div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card org-card mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-shield text-primary mr-2"></i>Company admins</span>
                    <span class="badge-soft">{{ $adminsCount }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table org-table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Account</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($admins as $admin)
                                    <tr>
                                        <td class="font-weight-bold">{{ $admin->name }}</td>
                                        <td class="text-muted">{{ $admin->email }}</td>
                                        <td>
                                            @if ($admin->is_suspended)
                                                <span class="badge badge-warning">Suspended</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No admins yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-3 mb-lg-0">
            <div class="card org-card mb-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users text-primary mr-2"></i>Sales agents</span>
                    <span class="badge-soft">{{ $agents->count() }} total</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table org-table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Agent</th>
                                    <th class="text-right">Tickets</th>
                                    <th class="text-right">Revenue</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agents as $agent)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold d-block">{{ $agent->name }}</span>
                                            <small class="text-muted">{{ $agent->email }}</small>
                                        </td>
                                        <td class="text-right font-weight-bold">{{ number_format($agent->tickets_count) }}</td>
                                        <td class="text-right text-success font-weight-bold">
                                            {{ number_format((float) ($agent->tickets_sum_price_paid ?? 0), 2) }}
                                        </td>
                                        <td>
                                            @if ($agent->is_suspended)
                                                <span class="badge badge-warning">Suspended</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            No agents yet. Company admins can add agents from their dashboard.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card org-card mb-0">
                <div class="card-header">
                    <i class="fas fa-calendar-check text-info mr-2"></i>Recent events
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentEvents as $ev)
                            <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                                <div>
                                    <div class="font-weight-bold">{{ $ev->title }}</div>
                                    <small class="text-muted">
                                        {{ $ev->start_at?->format('M j, Y g:i A') }}
                                        @if ($ev->status)
                                            · <span class="text-capitalize">{{ $ev->status }}</span>
                                        @endif
                                    </small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-light border">{{ $ev->tickets_count }} sold</span>
                                    @if ($ev->tickets_sum_price_paid !== null)
                                        <div class="small text-success font-weight-bold mt-1">
                                            {{ number_format((float) $ev->tickets_sum_price_paid, 2) }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center py-5">No events yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
