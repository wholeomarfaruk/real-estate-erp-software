<div x-data x-init="$store.pageName = { name: 'Payroll Payment History', slug: 'hrm-payroll-payments' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Payroll Payment History</h1>
            <p class="text-sm text-gray-500">View all payroll payments across employees and periods.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Payroll Payments</li>
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
                        placeholder="Search employee/reference/notes"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>
                <div class="lg:col-span-3">
                    <select wire:model.live="employeeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="methodFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Methods</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Employee</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Payroll</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Method</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Received By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($payment->payment_date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p class="font-medium">{{ $payment->payroll?->employee?->name ?: 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $payment->payroll?->employee?->employee_id ?: '' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($payment->payroll)
                                            <a href="{{ route('admin.hrm.payrolls.view', $payment->payroll) }}" class="text-indigo-600 hover:text-indigo-700">
                                                {{ \Carbon\Carbon::createFromDate($payment->payroll->year, $payment->payroll->month, 1)->format('F Y') }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : 'N/A' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $payment->reference_no ?: 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $payment->receiver?->name ?: 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No payment history found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($payments->hasPages())
                <div class="mt-6">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

