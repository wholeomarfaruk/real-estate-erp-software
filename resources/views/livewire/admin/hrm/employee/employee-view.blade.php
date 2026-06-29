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
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Actions</th>
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
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- View --}}
                                    <button type="button" wire:click="openViewSalaryStructureModal({{ $structure->id }})" title="View" class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    {{-- Edit --}}
                                    @can('hrm.salary-structures.update')
                                        <button type="button" wire:click="openEditSalaryStructureModal({{ $structure->id }})" title="Edit" class="rounded p-1 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828A2 2 0 0110 16H8v-2a2 2 0 01.586-1.414z" />
                                            </svg>
                                        </button>
                                        {{-- Toggle Status --}}
                                        <button type="button" wire:click="toggleSalaryStructureStatus({{ $structure->id }})" title="{{ $structure->status ? 'Deactivate' : 'Activate' }}" class="rounded p-1 transition {{ $structure->status ? 'text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700' : 'text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600' }}">
                                            @if ($structure->status)
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </button>
                                    @endcan
                                    {{-- Delete --}}
                                    @can('hrm.salary-structures.delete')
                                        <button type="button" wire:click="confirmDeleteSalaryStructure({{ $structure->id }})" title="Delete" class="rounded p-1 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">No salary structures found.</td>
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
                <h2 class="text-xl font-bold text-gray-900">{{ $editingSalaryStructureId ? 'Edit Salary Structure' : 'Add Salary Structure' }}</h2>
                <button type="button" @click="open = false; $wire.closeSalaryStructureModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="saveSalaryStructure" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Effective From <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="effective_from" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none flatpickr-only-date">
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
                        {{ $editingSalaryStructureId ? 'Update Structure' : 'Save Structure' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-cloak x-data x-show="$wire.confirmingDeleteId !== null" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-900">Delete Salary Structure</h2>
            </div>
            <p class="mt-3 text-sm text-gray-600">Are you sure you want to delete this salary structure? This action cannot be undone.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="cancelDeleteSalaryStructure" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="deleteSalaryStructure" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>

    {{-- View Salary Structure Modal --}}
    <div x-cloak x-data="{ open: @entangle('showViewSalaryStructureModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">Salary Structure Details</h2>
                <button type="button" wire:click="closeViewSalaryStructureModal" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @if ($viewingStructure)
                <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Effective From</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ optional($viewingStructure->effective_from)->format('d M, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Status</p>
                        <p class="mt-0.5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $viewingStructure->status ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-700' }}">
                                {{ $viewingStructure->status ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Basic Salary</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->basic_salary, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">House Rent</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->house_rent, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Medical Allowance</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->medical_allowance, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Transport Allowance</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->transport_allowance, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Food Allowance</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->food_allowance, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Other Allowance</p>
                        <p class="mt-0.5 font-medium text-gray-800">{{ number_format((float) $viewingStructure->other_allowance, 2) }}</p>
                    </div>
                    <div class="col-span-2 border-t border-gray-100 pt-3">
                        <p class="text-xs text-gray-500">Gross Salary</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-900">{{ number_format((float) $viewingStructure->gross_salary, 2) }}</p>
                    </div>
                    @if ($viewingStructure->notes)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500">Notes</p>
                            <p class="mt-0.5 text-gray-700">{{ $viewingStructure->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="closeViewSalaryStructureModal" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

