<div x-data x-init="$store.pageName = { name: 'Supplier Dashboard', slug: 'supplier-dashboard' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Dashboard</h1>
            <p class="text-sm text-gray-500">Supplier analytics, payable risk, return patterns, and recent operational activity.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Supplier</li>
                <li>/</li>
                <li class="text-gray-700">Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        @can('supplier.create')
            <a href="{{ route('admin.supplier.suppliers.create') }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">Add Supplier</a>
        @endcan
        @can('supplier.bill.create')
            <a href="{{ route('admin.supplier.bills.create') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">New Bill</a>
        @endcan
        @can('supplier.payment.create')
            <a href="{{ route('admin.supplier.payments.create') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">New Payment</a>
        @endcan
        @can('supplier.return.create')
            <a href="{{ route('admin.supplier.returns.create') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">New Return</a>
        @endcan
        @can('supplier.ledger.view')
            <a href="{{ route('admin.supplier.ledger.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Supplier Ledger</a>
        @endcan
        @can('supplier.reports.due')
            <a href="{{ route('admin.supplier.reports.due') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Due Report</a>
        @endcan
        @can('supplier.reports.aging')
            <a href="{{ route('admin.supplier.reports.aging') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Aging Report</a>
        @endcan
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Suppliers</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['total_suppliers']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Active Suppliers</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((int) $summary['active_suppliers']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Blocked Suppliers</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((int) $summary['blocked_suppliers']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Payable</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['total_payable'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Overdue Payable</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ number_format((float) $summary['overdue_payable'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Unapplied Advance</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) $summary['unapplied_advance'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">This Month Billed</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format((float) $summary['this_month_billed'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">This Month Paid</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((float) $summary['this_month_paid'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">This Month Return</p>
            <p class="mt-1 text-2xl font-semibold text-sky-700">{{ number_format((float) $summary['this_month_return'], 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">Alerts & Monitoring</h2>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($alerts as $alert)
                @php
                    [$toneBorder, $toneText, $toneBadge] = match ($alert['tone']) {
                        'rose' => ['border-rose-200', 'text-rose-700', 'bg-rose-100 text-rose-700'],
                        'amber' => ['border-amber-200', 'text-amber-700', 'bg-amber-100 text-amber-700'],
                        'emerald' => ['border-emerald-200', 'text-emerald-700', 'bg-emerald-100 text-emerald-700'],
                        'indigo' => ['border-indigo-200', 'text-indigo-700', 'bg-indigo-100 text-indigo-700'],
                        'blue' => ['border-blue-200', 'text-blue-700', 'bg-blue-100 text-blue-700'],
                        default => ['border-gray-200', 'text-gray-700', 'bg-gray-100 text-gray-700'],
                    };
                @endphp
                <div class="rounded-xl border {{ $toneBorder }} bg-white p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-700">{{ $alert['title'] }}</p>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $toneBadge }}">
                            {{ number_format((int) $alert['count']) }}
                        </span>
                    </div>
                    <p class="mt-2 text-xl font-semibold {{ $toneText }}">
                        {{ $alert['key'] === 'return_draft_issue' ? number_format((int) $alert['amount']).' stale' : number_format((float) $alert['amount'], 2) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">{{ $alert['meta'] }}</p>
                    @if ($alert['route'] && Route::has($alert['route']))
                        <a href="{{ route($alert['route']) }}" class="mt-3 inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700">
                            Review details
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Monthly Purchase vs Payment</h2>
            <p class="mt-1 text-xs text-gray-500">Last 12 months supplier billing and payment trend.</p>
            <div class="mt-3 h-80">
                <canvas id="supplier-monthly-chart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-700">Supplier Status Distribution</h2>
            <p class="mt-1 text-xs text-gray-500">Active vs inactive vs blocked suppliers.</p>
            <div class="mt-3 h-80">
                <canvas id="supplier-status-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Top Suppliers by Purchase Value</h2>
            <p class="mt-1 text-xs text-gray-500">Top 10 suppliers by cumulative posted bill amount.</p>
            <div class="mt-3 h-80">
                <canvas id="supplier-top-purchase-chart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-gray-700">Due Aging Distribution</h2>
            <p class="mt-1 text-xs text-gray-500">Current due and overdue buckets.</p>
            <div class="mt-3 h-80">
                <canvas id="supplier-aging-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 2xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Bills</h2>
                @can('supplier.bill.list')
                    <a href="{{ route('admin.supplier.bills.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Bill No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Supplier</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Bill Date</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Due Date</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Due Amount</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activity['recent_bills'] as $bill)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @can('supplier.bill.view')
                                        <a href="{{ route('admin.supplier.bills.view', $bill) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $bill->bill_no }}</a>
                                    @else
                                        {{ $bill->bill_no }}
                                    @endcan
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $bill->supplier?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($bill->bill_date)->format('d M, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($bill->due_date)->format('d M, Y') ?: 'N/A' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-rose-700">{{ number_format((float) $bill->due_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <x-supplier-bill-status-badge :status="$bill->status?->value" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No recent bills found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Payments</h2>
                @can('supplier.payment.list')
                    <a href="{{ route('admin.supplier.payments.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Payment No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Supplier</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Date</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Method</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activity['recent_payments'] as $payment)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @can('supplier.payment.view')
                                        <a href="{{ route('admin.supplier.payments.view', $payment) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $payment->payment_no }}</a>
                                    @else
                                        {{ $payment->payment_no }}
                                    @endcan
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $payment->supplier?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($payment->payment_date)->format('d M, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $payment->payment_method?->label() ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-emerald-700">{{ number_format((float) $payment->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No recent payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 2xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Returns</h2>
                @can('supplier.return.list')
                    <a href="{{ route('admin.supplier.returns.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Return No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Supplier</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Date</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Amount</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activity['recent_returns'] as $supplierReturn)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @can('supplier.return.view')
                                        <a href="{{ route('admin.supplier.returns.view', $supplierReturn) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $supplierReturn->return_no }}</a>
                                    @else
                                        {{ $supplierReturn->return_no }}
                                    @endcan
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $supplierReturn->supplier?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ optional($supplierReturn->return_date)->format('d M, Y') }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-sky-700">{{ number_format((float) $supplierReturn->total_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <x-supplier-return-status-badge :status="$supplierReturn->status?->value" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No recent returns found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Top Due Suppliers</h2>
                @can('supplier.reports.due')
                    <a href="{{ route('admin.supplier.reports.due') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View due report</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Supplier</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Due</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Overdue</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Last Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($activity['top_due_suppliers'] as $supplierDue)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <p class="font-medium text-gray-800">{{ $supplierDue->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $supplierDue->code ?: 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-rose-700">{{ number_format((float) $supplierDue->total_due_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm {{ (float) $supplierDue->overdue_amount > 0 ? 'text-amber-700' : 'text-gray-500' }}">{{ number_format((float) $supplierDue->overdue_amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $supplierDue->last_payment_date ? \Illuminate\Support\Carbon::parse($supplierDue->last_payment_date)->format('d M, Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No due suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const payload = @json($charts);

            if (!window.Chart || !payload) {
                return;
            }

            window.supplierDashboardCharts = window.supplierDashboardCharts || {};

            const instances = window.supplierDashboardCharts;

            const destroyChart = (key) => {
                if (instances[key]) {
                    instances[key].destroy();
                    instances[key] = null;
                }
            };

            const createChart = (key, canvasId, config) => {
                const canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                destroyChart(key);
                instances[key] = new Chart(canvas, config);
            };

            createChart('monthly', 'supplier-monthly-chart', {
                type: 'line',
                data: {
                    labels: payload.monthly_purchase_payment.labels,
                    datasets: [{
                            label: 'Purchase',
                            data: payload.monthly_purchase_payment.purchase,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.16)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        },
                        {
                            label: 'Payment',
                            data: payload.monthly_purchase_payment.payment,
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5, 150, 105, 0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
            });

            createChart('topPurchase', 'supplier-top-purchase-chart', {
                type: 'bar',
                data: {
                    labels: payload.top_suppliers_by_purchase.labels,
                    datasets: [{
                        label: 'Purchase Value',
                        data: payload.top_suppliers_by_purchase.values,
                        backgroundColor: 'rgba(79, 70, 229, 0.72)',
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        borderRadius: 6
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                },
            });

            createChart('aging', 'supplier-aging-chart', {
                type: 'doughnut',
                data: {
                    labels: payload.due_aging_distribution.labels,
                    datasets: [{
                        data: payload.due_aging_distribution.values,
                        backgroundColor: ['#0ea5e9', '#14b8a6', '#f59e0b', '#f97316', '#ef4444'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                },
            });

            createChart('status', 'supplier-status-chart', {
                type: 'pie',
                data: {
                    labels: payload.supplier_status_distribution.labels,
                    datasets: [{
                        data: payload.supplier_status_distribution.values,
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                },
            });
        })();
    </script>
@endpush
