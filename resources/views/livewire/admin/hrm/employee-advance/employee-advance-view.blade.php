<div x-data x-init="$store.pageName = { name: 'Employee Advance Details', slug: 'hrm-employee-advance-view' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Employee Advance</h1>
            <p class="text-sm text-gray-500">{{ $employeeAdvance->employee?->name }} ({{ $employeeAdvance->employee?->employee_id }})</p>
        </div>
        <a href="{{ route('admin.hrm.employee-advances.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
            Back
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Advance Amount</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format((float) $employeeAdvance->amount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Adjusted Amount</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format((float) $employeeAdvance->adjusted_amount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Remaining Amount</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format((float) $employeeAdvance->remaining_amount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Status</p>
            <p class="mt-2">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $employeeAdvance->status === 'cleared' ? 'bg-emerald-100 text-emerald-700' : ($employeeAdvance->status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-zinc-100 text-zinc-700') }}">
                    {{ ucfirst($employeeAdvance->status) }}
                </span>
            </p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="text-sm font-semibold text-gray-700">Advance Information</h2>
        <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-2">
            <p><span class="text-gray-500">Advance Date:</span> {{ optional($employeeAdvance->advance_date)->format('d M, Y') ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Department:</span> {{ $employeeAdvance->employee?->department?->name ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Designation:</span> {{ $employeeAdvance->employee?->designation?->name ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Transaction ID:</span> {{ $employeeAdvance->transaction_id ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Created By:</span> {{ $employeeAdvance->creator?->name ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Approved By:</span> {{ $employeeAdvance->approver?->name ?: 'N/A' }}</p>
            <p class="md:col-span-2"><span class="text-gray-500">Notes:</span> {{ $employeeAdvance->notes ?: 'N/A' }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="text-sm font-semibold text-gray-700">Adjustment History</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Adjustment Date</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Payroll</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Payroll Status</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employeeAdvance->adjustments as $adjustment)
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ optional($adjustment->adjustment_date)->format('d M, Y') }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">
                                <a href="{{ route('admin.hrm.payrolls.view', $adjustment->payroll_id) }}" class="text-indigo-600 hover:text-indigo-700">
                                    {{ \Carbon\Carbon::createFromDate($adjustment->payroll?->year, $adjustment->payroll?->month, 1)->format('F Y') }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ ucfirst($adjustment->payroll?->payment_status ?: 'N/A') }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-gray-700">{{ number_format((float) $adjustment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">No adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

