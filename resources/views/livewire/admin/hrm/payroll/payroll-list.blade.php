<div x-data x-init="$store.pageName = { name: 'Payrolls', slug: 'hrm-payrolls' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Payrolls</h1>
            <p class="text-sm text-gray-500">Generate monthly payroll and track payment status.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Payrolls</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search employee name or ID"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="employeeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <select wire:model.live="monthFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Month</option>
                        @foreach ($monthOptions as $month)
                            <option value="{{ $month }}">{{ \Carbon\Carbon::createFromDate(2000, $month, 1)->format('M') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <select wire:model.live="yearFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Year</option>
                        @foreach ($yearOptions as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="paymentStatusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Payment Status</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-3">
                    @can('hrm.payrolls.create')
                        <button type="button" wire:click="openGenerateModal" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                            Generate Payroll
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Period</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Employee</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Gross</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Net</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Paid</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Due</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payrolls as $payroll)
                                @php
                                    $paid = round((float) ($payroll->total_paid ?? 0), 2);
                                    $due = round(max(0, (float) $payroll->net_salary - $paid), 2);
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p class="font-medium">{{ $payroll->employee?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $payroll->employee?->employee_id ?? '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $payroll->gross_salary, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $payroll->net_salary, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format($paid, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format($due, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $payroll->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($payroll->payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-zinc-100 text-zinc-700') }}">
                                            {{ ucfirst($payroll->payment_status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.hrm.payrolls.view', $payroll) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No payrolls found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or generate a payroll.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($payrolls->hasPages())
                <div class="mt-6">
                    {{ $payrolls->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showGenerateModal') }" x-show="open" x-transition class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4">
        <div class="mx-auto mt-10 w-full max-w-5xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Generate Payroll</h2>
                <button type="button" @click="open = false; $wire.closeGenerateModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="generatePayroll" class="mt-4 space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Employee <span class="text-rose-500">*</span></label>
                        <select wire:model.live="employee_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="">Select employee</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->employee_id }})</option>
                            @endforeach
                        </select>
                        @error('employee_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Month</label>
                        <select wire:model.defer="month" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                            @foreach ($monthOptions as $month)
                                <option value="{{ $month }}">{{ \Carbon\Carbon::createFromDate(2000, $month, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        @error('month') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Year</label>
                        <select wire:model.defer="year" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        @error('year') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Payroll Date</label>
                        <input type="date" wire:model.defer="payroll_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @error('payroll_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-sm font-medium text-gray-700">Notes</label>
                        <input type="text" wire:model.defer="notes" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Bonus Items</h3>
                        <button type="button" wire:click="addBonusRow" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Add Bonus</button>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach ($bonus_items as $index => $item)
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                <div class="md:col-span-7">
                                    <input type="text" wire:model.defer="bonus_items.{{ $index }}.label" placeholder="Bonus label" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div class="md:col-span-4">
                                    <input type="number" min="0" step="0.01" wire:model.defer="bonus_items.{{ $index }}.amount" placeholder="0.00" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div class="md:col-span-1">
                                    <button type="button" wire:click="removeBonusRow({{ $index }})" class="h-10 w-full rounded-lg border border-rose-200 text-sm text-rose-600 hover:bg-rose-50">X</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Deduction Items</h3>
                        <button type="button" wire:click="addDeductionRow" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Add Deduction</button>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach ($deduction_items as $index => $item)
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-12">
                                <div class="md:col-span-7">
                                    <input type="text" wire:model.defer="deduction_items.{{ $index }}.label" placeholder="Deduction label" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div class="md:col-span-4">
                                    <input type="number" min="0" step="0.01" wire:model.defer="deduction_items.{{ $index }}.amount" placeholder="0.00" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div class="md:col-span-1">
                                    <button type="button" wire:click="removeDeductionRow({{ $index }})" class="h-10 w-full rounded-lg border border-rose-200 text-sm text-rose-600 hover:bg-rose-50">X</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-700">Advance Adjustments</h3>
                    <p class="mt-1 text-xs text-gray-500">Enter amount to adjust from pending employee advances.</p>
                    <div class="mt-3 space-y-2">
                        @forelse ($pendingAdvances as $advance)
                            <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 p-3 md:grid-cols-12">
                                <div class="md:col-span-7 text-sm text-gray-700">
                                    <p>Date: {{ optional($advance->advance_date)->format('d M, Y') }}</p>
                                    <p class="text-xs text-gray-500">Remaining: {{ number_format((float) $advance->remaining_amount, 2) }}</p>
                                </div>
                                <div class="md:col-span-5">
                                    <input type="number" min="0" max="{{ $advance->remaining_amount }}" step="0.01" wire:model.defer="advance_adjustments.{{ $advance->id }}" placeholder="0.00" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No pending advances for selected employee.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeGenerateModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        Generate Payroll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

