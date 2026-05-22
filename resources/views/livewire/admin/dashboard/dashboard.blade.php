<div x-data x-init="$store.pageName = { name: 'Dashboard', slug: 'dashboard' }" class="space-y-6">

    {{-- ════════════════════════════════════════════ WELCOME HEADER ══ --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900 text-white text-lg font-bold shadow">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <h1 class="text-base font-bold text-gray-900">
                    Welcome back, {{ auth()->user()->name ?? 'User' }}
                </h1>
                <div class="mt-0.5 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 border border-indigo-200">
                        {{ $roleLabel }}
                    </span>
                    <span class="text-xs text-gray-400">{{ now()->format('l, d F Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Quick action buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @can('accounts.banking.view')
            <a href="{{ route('admin.accounts.banking.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-4 0v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                Banking
            </a>
            @endcan
            @can('accounts.expense.list')
            <a href="{{ route('admin.accounts.expenses.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-3.5 w-3.5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Expenses
            </a>
            @endcan
            @can('purchase_order.list')
            <a href="{{ route('admin.inventory.purchase-orders.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-3.5 w-3.5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Purchase Orders
            </a>
            @endcan
            @can('accounts.bank.list')
            <a href="{{ route('admin.accounts.banks.index') }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Banks
            </a>
            @endcan
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════ KPI CARDS ══ --}}
    @if(count($kpis) > 0)
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($kpis as $kpi)
        @php
            $colors = [
                'indigo'  => ['bg' => 'bg-indigo-50',  'icon' => 'bg-indigo-600',  'text' => 'text-indigo-600',  'border' => 'border-indigo-100'],
                'emerald' => ['bg' => 'bg-emerald-50', 'icon' => 'bg-emerald-600', 'text' => 'text-emerald-600', 'border' => 'border-emerald-100'],
                'rose'    => ['bg' => 'bg-rose-50',    'icon' => 'bg-rose-600',    'text' => 'text-rose-600',    'border' => 'border-rose-100'],
                'amber'   => ['bg' => 'bg-amber-50',   'icon' => 'bg-amber-500',   'text' => 'text-amber-600',   'border' => 'border-amber-100'],
                'blue'    => ['bg' => 'bg-blue-50',    'icon' => 'bg-blue-600',    'text' => 'text-blue-600',    'border' => 'border-blue-100'],
                'violet'  => ['bg' => 'bg-violet-50',  'icon' => 'bg-violet-600',  'text' => 'text-violet-600',  'border' => 'border-violet-100'],
            ];
            $c = $colors[$kpi['color']] ?? $colors['indigo'];
        @endphp
        <div class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} px-5 py-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500 truncate">{{ $kpi['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 leading-none">{{ $kpi['value'] }}</p>
                    <p class="mt-1.5 text-xs {{ $c['text'] }} truncate">{{ $kpi['sub'] }}</p>
                </div>
                <div class="{{ $c['icon'] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-sm ml-2">
                    @if($kpi['icon'] === 'bank')
                    <svg class="h-4.5 w-4.5 text-white h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    @elseif($kpi['icon'] === 'income')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    @elseif($kpi['icon'] === 'expense')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    @elseif($kpi['icon'] === 'pending')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    @elseif($kpi['icon'] === 'po')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    @elseif($kpi['icon'] === 'stock')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    @elseif($kpi['icon'] === 'users')
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ════════════════════════════════ TWO-COLUMN: BANKING + EXPENSES ══ --}}
    @if($isFinance)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Recent Banking Requests --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Recent Banking Requests</h2>
                @can('accounts.banking.view')
                <a href="{{ route('admin.accounts.banking.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
                @endcan
            </div>
            @if($recentBankingRequests->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400 italic">No banking requests yet.</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentBankingRequests as $req)
                @php
                    $badge = match($req->status) {
                        'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                        'approved'  => 'bg-blue-50 text-blue-700 border-blue-200',
                        'released'  => 'bg-violet-50 text-violet-700 border-violet-200',
                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'rejected'  => 'bg-red-50 text-red-700 border-red-200',
                        default     => 'bg-gray-100 text-gray-600 border-gray-200',
                    };
                @endphp
                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $req->request_no }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                            {{ $req->bankAccount?->bank_name ?? '—' }}
                            · {{ $req->requestedBy?->name ?? '—' }}
                        </p>
                    </div>
                    <div class="ml-3 flex flex-col items-end gap-1">
                        <span class="text-xs font-semibold text-gray-800">{{ number_format($req->amount, 2) }}</span>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $badge }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Recent Expenses --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Recent Expenses</h2>
                @can('accounts.expense.list')
                <a href="{{ route('admin.accounts.expenses.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
                @endcan
            </div>
            @if($recentExpenses->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400 italic">No expenses yet.</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentExpenses as $exp)
                @php
                    $expBadge = match($exp->status) {
                        'draft'   => 'bg-gray-100 text-gray-600 border-gray-200',
                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'posted'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default   => 'bg-gray-100 text-gray-500 border-gray-200',
                    };
                @endphp
                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $exp->title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $exp->date->format('d M Y') }}
                            @if($exp->transactionCategory)
                             · {{ $exp->transactionCategory->name }}
                            @endif
                        </p>
                    </div>
                    <div class="ml-3 flex flex-col items-end gap-1">
                        <span class="text-xs font-semibold text-gray-800">{{ number_format($exp->amount, 2) }}</span>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $expBadge }}">
                            {{ ucfirst($exp->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
    @endif

    {{-- ════════════════════════════════════ PURCHASE ORDERS + BANK LIST ══ --}}
    @if($isPurchase || $isFinance)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Recent Purchase Orders --}}
        @if($isPurchase)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Recent Purchase Orders</h2>
                @can('purchase_order.list')
                <a href="{{ route('admin.inventory.purchase-orders.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
                @endcan
            </div>
            @if($recentPOs->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400 italic">No purchase orders yet.</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentPOs as $po)
                @php
                    $poBadge = match($po->status) {
                        'pending'              => 'bg-amber-50 text-amber-700 border-amber-200',
                        'engineer_approved'    => 'bg-blue-50 text-blue-700 border-blue-200',
                        'accounts_approved'    => 'bg-violet-50 text-violet-700 border-violet-200',
                        'chairman_approved'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'completed'            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'cancelled'            => 'bg-red-50 text-red-700 border-red-200',
                        default                => 'bg-gray-100 text-gray-600 border-gray-200',
                    };
                @endphp
                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $po->po_no }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $po->supplier?->name ?? '—' }}
                            · {{ optional($po->order_date)->format('d M Y') ?? '—' }}
                        </p>
                    </div>
                    <div class="ml-3 flex flex-col items-end gap-1">
                        <span class="text-xs font-semibold text-gray-800">
                            {{ number_format($po->approved_amount ?? $po->fund_request_amount ?? 0, 2) }}
                        </span>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $poBadge }}">
                            {{ ucwords(str_replace('_', ' ', $po->status)) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Bank Accounts Overview --}}
        @if($isFinance)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Bank Accounts Overview</h2>
                @can('accounts.bank.list')
                <a href="{{ route('admin.accounts.banks.index') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Manage →</a>
                @endcan
            </div>
            @if($bankSummary->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400 italic">No active bank accounts.</div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($bankSummary as $bank)
                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $bank['name'] }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $bank['code'] ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-sm font-bold {{ $bank['balance'] >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                            {{ number_format($bank['balance'], 2) }}
                        </p>
                        <p class="text-[10px] text-gray-400 capitalize">{{ $bank['type'] ?? '—' }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

    </div>
    @endif

    {{-- ═══════════════════════════════════════════ LOW STOCK ALERTS ══ --}}
    @if($isStore || $isAdmin)
    @if($lowStockItems->isNotEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 shadow-sm overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-4 border-b border-amber-200">
            <svg class="h-4 w-4 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <h2 class="text-sm font-semibold text-amber-800">Low Stock / Out of Stock Alerts</h2>
            <span class="ml-auto inline-flex rounded-full bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-800">
                {{ $lowStockItems->count() }} item(s)
            </span>
        </div>
        <div class="divide-y divide-amber-100">
            @foreach($lowStockItems as $item)
            <div class="flex items-center justify-between px-5 py-2.5">
                <div>
                    <p class="text-xs font-medium text-gray-800">{{ $item->product?->name ?? 'Unknown Product' }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">{{ $item->store?->name ?? '—' }}</p>
                </div>
                <div class="text-right">
                    <span class="{{ $item->quantity <= 0 ? 'text-red-600' : 'text-amber-700' }} text-xs font-bold">
                        {{ number_format($item->quantity, 2) }} units
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    {{-- ════════════════════════════════════════════ NO WIDGETS FALLBACK ══ --}}
    @if(count($kpis) === 0 && $recentBankingRequests->isEmpty() && $recentPOs->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white px-8 py-14 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
            <svg class="h-7 w-7 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-700">Dashboard is ready</p>
        <p class="mt-1 text-xs text-gray-400">No data is available for your role yet. Contact the administrator to assign the correct permissions.</p>
    </div>
    @endif

</div>
