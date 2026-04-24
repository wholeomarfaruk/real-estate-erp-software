<div x-data x-init="$store.pageName = { name: '{{ $employee ? 'Edit Employee' : 'Create Employee' }}', slug: 'hrm-employee-form' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $employee ? 'Edit Employee' : 'Create Employee' }}</h1>
            <p class="text-sm text-gray-500">Keep employee and login user separate. Link user only when needed.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li><a href="{{ route('admin.hrm.employees.index') }}" class="hover:text-gray-700">Employees</a></li>
                <li>/</li>
                <li class="text-gray-700">{{ $employee ? 'Edit' : 'Create' }}</li>
            </ol>
        </nav>
    </div>

    <form wire:submit.prevent="save" class="mt-4 space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700">Basic Information</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Employee ID <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="employee_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('employee_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" wire:model.defer="phone" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model.defer="email" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Gender</label>
                    <select wire:model.defer="gender" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select gender</option>
                        @foreach ($genderOptions as $option)
                            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" wire:model.defer="date_of_birth" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('date_of_birth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700">Employment Information</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Department</label>
                    <select wire:model.defer="department_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Designation</label>
                    <select wire:model.defer="designation_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select designation</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                        @endforeach
                    </select>
                    @error('designation_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Joining Date <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model.defer="joining_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('joining_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Confirmation Date</label>
                    <input type="date" wire:model.defer="confirmation_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('confirmation_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Exit Date</label>
                    <input type="date" wire:model.defer="exit_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('exit_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Employment Type</label>
                    <select wire:model.defer="employment_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select type</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    @error('employment_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Basic Salary</label>
                    <input type="number" min="0" step="0.01" wire:model.defer="basic_salary" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('basic_salary') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select wire:model.defer="status" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700">Login & Photo</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Linked User (Optional)</label>
                    <select wire:model.live="user_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">No Login User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">Has login: {{ $has_login ? 'Yes' : 'No' }}</p>
                </div>
                <div>
                    <x-media-picker-field
                        field="photo_file_id"
                        :value="$photo_file_id"
                        placeholder="Select employee photo"
                        :multiple="false"
                        type="image"
                        label="Photo"
                        required="false"
                    />
                    @error('photo_file_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-700">Address & Notes</h2>
            <div class="mt-4 grid grid-cols-1 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Address</label>
                    <textarea wire:model.defer="address" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                    @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Notes</label>
                    <textarea wire:model.defer="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                    @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 z-10 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-end gap-2">
                <a href="{{ $employee ? route('admin.hrm.employees.view', $employee) : route('admin.hrm.employees.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                    {{ $employee ? 'Update Employee' : 'Save Employee' }}
                </button>
            </div>
        </div>
    </form>
</div>

