<x-app-layout>
    <x-slot name="header">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">SMS Accounting</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('super-admin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Accounting</li>
                </ol>
            </div>
        </div>
    </x-slot>

    <div class="row mb-3">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($summary['total_revenue_etb'], 2) }}</h3>
                    <p>Total revenue (ETB)</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($summary['sms_package_revenue_etb'], 2) }}</h3>
                    <p>SMS packages ({{ number_format($summary['sms_package_sales_count']) }} sales)</p>
                </div>
                <div class="icon"><i class="fas fa-box"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($summary['sender_id_revenue_etb'], 2) }}</h3>
                    <p>Sender ID ({{ number_format($summary['sender_id_count']) }} sold)</p>
                </div>
                <div class="icon"><i class="fas fa-id-badge"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($summary['sms_sent_count']) }}</h3>
                    <p>SMS sent platform-wide</p>
                </div>
                <div class="icon"><i class="fas fa-sms"></i></div>
                <span class="small-box-footer">{{ number_format($summary['sms_consumed_etb'], 2) }} ETB wallet value used</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title mb-0">Revenue by organization</h3></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Organization</th>
                                <th class="text-right">SMS sent</th>
                                <th class="text-right">Wallet used</th>
                                <th class="text-right">Package revenue</th>
                                <th class="text-right">Sender ID</th>
                                <th class="text-right">Total paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $row)
                                <tr>
                                    <td><a href="{{ route('super-admin.companies.show', $row->company_id) }}">{{ $row->company_name }}</a></td>
                                    <td class="text-right">{{ number_format($row->sms_sent) }}</td>
                                    <td class="text-right">{{ number_format($row->sms_consumed_etb, 2) }} ETB</td>
                                    <td class="text-right">{{ number_format($row->package_revenue_etb, 2) }} ETB</td>
                                    <td class="text-right">{{ number_format($row->sender_id_revenue_etb, 2) }} ETB</td>
                                    <td class="text-right font-weight-bold">{{ number_format($row->total_revenue_etb, 2) }} ETB</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No SMS revenue recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Recent package sales</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentPackageSales as $sale)
                        <li class="list-group-item small py-2">
                            <strong>{{ $sale->company->name ?? '—' }}</strong><br>
                            {{ number_format($sale->amount_etb, 2) }} ETB · {{ $sale->created_at->format('M j, Y') }}
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center small">No sales yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title mb-0">Recent sender ID sales</h3></div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentSenderIdSales as $sale)
                        <li class="list-group-item small py-2">
                            <strong>{{ $sale->company->name ?? '—' }}</strong> — <code>{{ $sale->requested_sender_id }}</code><br>
                            {{ number_format($sale->price_etb, 2) }} ETB · {{ $sale->processed_at?->format('M j, Y') }}
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center small">No sender ID sales yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
