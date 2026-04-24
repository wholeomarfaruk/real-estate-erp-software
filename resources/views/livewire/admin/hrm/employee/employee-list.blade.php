<div x-data x-init="$store.pageName = { name: 'Employees', slug: 'hrm-employees' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Employees</h1>
            <p class="text-sm text-gray-500">Manage employee records, status, and login linkage.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Employees</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search ID, name, phone, email"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="departmentFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="designationFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Designations</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    @can('hrm.employees.create')
                        <a href="{{ route('admin.hrm.employees.create') }}" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                            Create
                        </a>
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Employee</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Contact</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Department / Designation</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Basic Salary</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Login</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $employee->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $employee->employee_id }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $employee->phone ?: 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $employee->email ?: 'N/A' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $employee->department?->name ?: 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $employee->designation?->name ?: 'N/A' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $employee->basic_salary, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $employee->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-700' }}">
                                            {{ ucfirst($employee->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $employee->has_login ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $employee->has_login ? 'Has Login' : 'No Login' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900">
                                                <span class="sr-only">Actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 z-40 mt-10 w-48 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('hrm.employees.view')
                                                    <a href="{{ route('admin.hrm.employees.view', $employee) }}" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan
                                                @can('hrm.employees.update')
                                                    <a href="{{ route('admin.hrm.employees.edit', $employee) }}" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No employees found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or create an employee.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($employees->hasPages())
                <div class="mt-6">
                    {{ $employees->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

