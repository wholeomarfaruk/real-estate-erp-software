<div x-data x-init="$store.pageName = { name: 'Expenses', slug: 'expenses' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-800">Expenses</li>
            </ol>
        </nav>
        @can('accounts.expense.create')
        <a href="{{ route('admin.accounts.expenses.create') }}" wire:navigate
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Expense
        </a>
        @endcan
    </div>

    {{-- KPI Strip --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Draft</p>
            <p class="mt-1 text-2xl font-bold text-gray-700">{{ number_format($kpi->draft_count ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-xs text-amber-600">Pending</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($kpi->pending_count ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <p class="text-xs text-emerald-600">Posted</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($kpi->posted_count ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
            <p class="text-xs text-indigo-600">Total Posted</p>
            <p class="mt-1 text-xl font-bold text-indigo-700">{{ number_format($kpi->total_posted ?? 0, 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <div class="relative">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search expenses…"
                class="h-9 rounded-lg border border-gray-300 pl-8 pr-3 text-sm focus:border-indigo-500 focus:outline-none min-w-52">
        </div>

        <select wire:model.live="statusFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="posted">Posted</option>
        </select>

        <input type="date" wire:model.live="dateFrom"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
        <input type="date" wire:model.live="dateTo"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">

        <select wire:model.live="accountFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none max-w-48">
            <option value="">All Accounts</option>
            @foreach($expenseAccounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left font-medium">Expense No</th>
                        <th class="px-5 py-3 text-left font-medium">Title</th>
                        <th class="px-5 py-3 text-left font-medium">Date</th>
                        <th class="px-5 py-3 text-left font-medium">Category</th>
                        <th class="px-5 py-3 text-left font-medium">Account</th>
                        <th class="px-5 py-3 text-right font-medium">Amount</th>
                        <th class="px-5 py-3 text-center font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-mono text-xs text-gray-500">
                            {{ $expense->expense_no ?? '—' }}
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-800 max-w-xs truncate">
                            {{ $expense->title }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                            {{ $expense->date->format('d M, Y') }}
                        </td>
                        <td class="px-5 py-3 text-gray-600">
                            {{ $expense->transactionCategory?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-600 text-xs">
                            {{ $expense->expenseAccount?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-800">
                            {{ number_format($expense->amount, 2) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $expense->statusBadgeClass() }}">
                                {{ $expense->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.accounts.expenses.show', $expense) }}" wire:navigate
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition inline-flex">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400 italic">
                            No expenses found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="border-t border-gray-100 px-5 py-3">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>

</div>
