<div x-data x-init="$store.pageName = { name: 'Employee Details', slug: 'hrm-employee-view' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Employee Details</h1>
            <p class="text-sm text-gray-500">{{ $employee->name }} ({{ $employee->employee_id }})</p>
        </div>

        <div class="flex items-center gap-2">
            @can('hrm.employees.update')
                <a href="{{ route('admin.hrm.employees.edit', $employee) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                    Edit
                </a>
            @endcan
            <a href="{{ route('admin.hrm.employees.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back
            </a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Total Payrolls</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ $summary['total_payrolls'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Total Net Salary</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format($summary['total_net_salary'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Total Advances</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format($summary['total_advances'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <p class="text-xs text-gray-500">Advance Remaining</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format($summary['total_advance_remaining'], 2) }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Basic Information</h2>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-2">
                <p><span class="text-gray-500">Department:</span> {{ $employee->department?->name ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Designation:</span> {{ $employee->designation?->name ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $employee->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $employee->email ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Joining Date:</span> {{ optional($employee->joining_date)->format('d M, Y') ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Employment Type:</span> {{ $employee->employment_type ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Status:</span> {{ ucfirst($employee->status) }}</p>
                <p><span class="text-gray-500">Has Login:</span> {{ $employee->has_login ? 'Yes' : 'No' }}</p>
            </div>
            @if ($employee->address || $employee->notes)
                <div class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-700">
                    <p><span class="text-gray-500">Address:</span> {{ $employee->address ?: 'N/A' }}</p>
                    <p class="mt-2"><span class="text-gray-500">Notes:</span> {{ $employee->notes ?: 'N/A' }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-gray-700">Photo</h2>
            <div class="mt-4">
                @if ($employee->photo_file_id)
                    <img src="{{ file_path($employee->photo_file_id) }}" alt="{{ $employee->name }}" class="h-44 w-full rounded-lg object-cover">
                @else
                    <div class="grid h-44 place-content-center rounded-lg border border-dashed border-gray-300 bg-gray-50 text-sm text-gray-500">
                        No Photo
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-gray-700">Salary Structures</h2>
            @can('hrm.salary-structures.create')
                <button type="button" wire:click="openSalaryStructureModal" class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800">
                    Add Structure
                </button>
            @endcan
        </div>
        <div class="max-w-full overflow-x-auto p-5 sm:p-6">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Effective From</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Basic</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Allowances</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Gross</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($salaryStructures as $structure)
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ optional($structure->effective_from)->format('d M, Y') }}</td>
                            <td class="px-3 py-2 text-right text-sm text-gray-700">{{ number_format((float) $structure->basic_salary, 2) }}</td>
                            <td class="px-3 py-2 text-right text-sm text-gray-700">
                                {{ number_format((float) ($structure->gross_salary - $structure->basic_salary), 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-gray-700">{{ number_format((float) $structure->gross_salary, 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $structure->status ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-700' }}">
                                    {{ $structure->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">No salary structures found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($salaryStructures->hasPages())
                <div class="mt-4">
                    {{ $salaryStructures->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-gray-700">Recent Payrolls</h2>
            <div class="mt-3 space-y-3">
                @forelse ($payrolls as $payroll)
                    <a href="{{ route('admin.hrm.payrolls.view', $payroll) }}" class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <div>
                            <p>{{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($payroll->payment_status) }}</p>
                        </div>
                        <p class="font-medium">{{ number_format((float) $payroll->net_salary, 2) }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">No payroll records yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="text-sm font-semibold text-gray-700">Recent Advances</h2>
            <div class="mt-3 space-y-3">
                @forelse ($advances as $advance)
                    <a href="{{ route('admin.hrm.employee-advances.view', $advance) }}" class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <div>
                            <p>{{ optional($advance->advance_date)->format('d M, Y') }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($advance->status) }}</p>
                        </div>
                        <p class="font-medium">{{ number_format((float) $advance->amount, 2) }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">No advance records yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <h2 class="text-sm font-semibold text-gray-700">Salary Payment History</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Payroll</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($paymentHistory as $payment)
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ optional($payment->payment_date)->format('d M, Y') }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ \Carbon\Carbon::createFromDate($payment->payroll->year, $payment->payroll->month, 1)->format('F Y') }}</td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-gray-700">{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-sm text-gray-500">No salary payment history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showSalaryStructureModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4">
        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Add Salary Structure</h2>
                <button type="button" @click="open = false; $wire.closeSalaryStructureModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="saveSalaryStructure" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Effective From <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="effective_from" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('effective_from') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Basic Salary</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="basic_salary" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('basic_salary') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">House Rent</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="house_rent" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('house_rent') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Medical Allowance</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="medical_allowance" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('medical_allowance') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Transport Allowance</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="transport_allowance" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('transport_allowance') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Food Allowance</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="food_allowance" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('food_allowance') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Other Allowance</label>
                    <input type="number" step="0.01" min="0" wire:model.defer="other_allowance" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('other_allowance') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model.defer="notes" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <label class="md:col-span-2 inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.defer="status" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Active
                </label>
                <div class="md:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeSalaryStructureModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        Save Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

