<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Account Entry Types</h1>
            <p class="mt-2 text-sm text-gray-600">Manage dynamic entry types (locked entries are system-defined)</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('admin.account-entries.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                ← Back to Hub
            </a>
            <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                + Add New Entry Type
            </button>
        </div>
    </div>

    <!-- Table Section -->
    @if ($entries->count() > 0)
        <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Workflow</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Accounts</th>
                        <th class="relative px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($entries as $entry)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $entry->name }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $entry->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $entry->category->title }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst(str_replace('_', ' ', $entry->workflow->value)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($entry->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="space-y-1">
                                    @if ($entry->debit_feature_type)
                                        <div>DR: {{ $entry->debit_feature_type }}</div>
                                    @elseif ($entry->debit_account_group)
                                        <div>DR: {{ $entry->debit_account_group }}</div>
                                    @endif
                                    @if ($entry->credit_feature_type)
                                        <div>CR: {{ $entry->credit_feature_type }}</div>
                                    @elseif ($entry->credit_account_group)
                                        <div>CR: {{ $entry->credit_account_group }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex gap-2 justify-end">
                                    <button type="button" wire:click="openEditModal({{ $entry->id }})" class="text-blue-600 hover:text-blue-900 inline-flex items-center px-3 py-1.5 border border-blue-300 rounded-md text-xs font-medium hover:bg-blue-50 transition-colors duration-200">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="toggleActive({{ $entry->id }})" class="text-gray-600 hover:text-gray-900 inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium hover:bg-gray-50 transition-colors duration-200">
                                        {{ $entry->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button type="button" wire:click="deleteEntry({{ $entry->id }})" onclick="return confirm('Delete this entry type?')" class="text-red-600 hover:text-red-900 inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-xs font-medium hover:bg-red-50 transition-colors duration-200">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No entry types</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new dynamic entry type.</p>
        </div>
    @endif

    <!-- Create Modal -->
    <x-modal wire:model="showCreateModal" maxWidth="xl">
        <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
            <div class="space-y-6 p-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Create New Entry Type</h3>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                <!-- Name & Slug Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g., Custom Receipt" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        @error('name') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                        <input type="text" wire:model="slug" placeholder="e.g., custom-receipt" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        @error('slug') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" placeholder="What does this entry type do?" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" rows="2"></textarea>
                    @error('description') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <!-- Category & Workflow Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select wire:model="category_key" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->key }}">{{ $cat->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Workflow *</label>
                        <select wire:model="workflow" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="banking_request">Banking Request</option>
                            <option value="direct_ledger">Direct Ledger</option>
                            <option value="posting_engine">Posting Engine</option>
                        </select>
                    </div>
                </div>

                @if ($workflow === 'posting_engine')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Accounting Event Key</label>
                        <input type="text" wire:model="accounting_event_key" placeholder="e.g., expense.payment" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Available: expense.payment, hrm.salary_payment, property.rent_collection, etc.</p>
                    </div>
                @endif

                <!-- Transaction Type & Permission Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <input type="text" wire:model="transaction_type" placeholder="e.g., custom_receipt" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                        <input type="text" wire:model="permission" placeholder="e.g., accounts.entry.receipts.create" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <!-- Debit Account Source -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Debit Account Source</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Feature Type</label>
                            <select wire:model="debit_feature_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Group</label>
                            <select wire:model="debit_account_group" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Account Type</label>
                        <input type="text" wire:model="debit_account_type" placeholder="cash,bank,mfs" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Comma-separated: cash, bank, mfs, wallet</p>
                    </div>
                </div>

                <!-- Credit Account Source -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Credit Account Source</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Feature Type</label>
                            <select wire:model="credit_feature_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Group</label>
                            <select wire:model="credit_account_group" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Account Type</label>
                        <input type="text" wire:model="credit_account_type" placeholder="cash,bank" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Active & Visible Checkboxes -->
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_visible" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Visible</span>
                    </label>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" wire:model="sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" wire:click="closeModals" class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        Create Entry Type
                    </button>
                </div>
            </form>
            </div>
        </div>
    </x-modal>

    <!-- Edit Modal -->
    <x-modal wire:model="showEditModal" maxWidth="xl">
        <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
            <div class="space-y-6 p-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Edit Entry Type</h3>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                <!-- Name & Slug Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g., Custom Receipt" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        @error('name') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug (Read-only)</label>
                        <input type="text" wire:model="slug" disabled class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500 text-sm">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="description" placeholder="What does this entry type do?" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm" rows="2"></textarea>
                    @error('description') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <!-- Category & Workflow Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select wire:model="category_key" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->key }}">{{ $cat->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Workflow *</label>
                        <select wire:model="workflow" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="banking_request">Banking Request</option>
                            <option value="direct_ledger">Direct Ledger</option>
                            <option value="posting_engine">Posting Engine</option>
                        </select>
                    </div>
                </div>

                @if ($workflow === 'posting_engine')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Accounting Event Key</label>
                        <input type="text" wire:model="accounting_event_key" placeholder="e.g., expense.payment" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Available: expense.payment, hrm.salary_payment, property.rent_collection, etc.</p>
                    </div>
                @endif

                <!-- Transaction Type & Permission Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                        <input type="text" wire:model="transaction_type" placeholder="e.g., custom_receipt" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                        <input type="text" wire:model="permission" placeholder="e.g., accounts.entry.receipts.create" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <!-- Debit Account Source -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Debit Account Source</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Feature Type</label>
                            <select wire:model="debit_feature_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Group</label>
                            <select wire:model="debit_account_group" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Account Type</label>
                        <input type="text" wire:model="debit_account_type" placeholder="cash,bank,mfs" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Comma-separated: cash, bank, mfs, wallet</p>
                    </div>
                </div>

                <!-- Credit Account Source -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Credit Account Source</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Feature Type</label>
                            <select wire:model="credit_feature_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Account Group</label>
                            <select wire:model="credit_account_group" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Account Type</label>
                        <input type="text" wire:model="credit_account_type" placeholder="cash,bank" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Active & Visible Checkboxes -->
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_visible" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Visible</span>
                    </label>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" wire:model="sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="button" wire:click="closeModals" class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        Update Entry Type
                    </button>
                </div>
            </form>
            </div>
        </div>
    </x-modal>
</div>
