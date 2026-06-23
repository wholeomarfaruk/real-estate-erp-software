<div class="w-full">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h2>
            @if ($category->description)
                <p class="mt-2 text-sm text-gray-600">{{ $category->description }}</p>
            @endif
        </div>

        {{-- Form --}}
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Expense Account --}}
                <div>
                    <label for="expense_account_id" class="block text-sm font-medium text-gray-700">Expense Head *</label>
                    <select
                        id="expense_account_id"
                        wire:model="expense_account_id"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option value="">Select an expense account</option>
                        @foreach ($expenseAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_account_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Payment Account --}}
                <div>
                    <label for="payment_account_id" class="block text-sm font-medium text-gray-700">Payment From *</label>
                    <select
                        id="payment_account_id"
                        wire:model="payment_account_id"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option value="">Select a payment account</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->type?->label() ?? '' }})</option>
                        @endforeach
                    </select>
                    @error('payment_account_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Payment Method --}}
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method *</label>
                    <select
                        id="payment_method"
                        wire:model="payment_method"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount *</label>
                    <input
                        type="number"
                        id="amount"
                        wire:model="amount"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="0.00"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    required
                    maxlength="200"
                    placeholder="e.g., Office Rent - June 2026"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Date --}}
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date *</label>
                <input
                    type="date"
                    id="date"
                    wire:model="date"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                @error('date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Reference Number --}}
            <div>
                <label for="reference_no" class="block text-sm font-medium text-gray-700">Reference Number (Optional)</label>
                <input
                    type="text"
                    id="reference_no"
                    wire:model="reference_no"
                    maxlength="100"
                    placeholder="e.g., INV-2026-001"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                @error('reference_no')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Paid To Name --}}
                <div>
                    <label for="paid_to_name" class="block text-sm font-medium text-gray-700">Paid To (Optional)</label>
                    <input
                        type="text"
                        id="paid_to_name"
                        wire:model="paid_to_name"
                        maxlength="200"
                        placeholder="Vendor or recipient name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    @error('paid_to_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Paid To Phone --}}
                <div>
                    <label for="paid_to_phone" class="block text-sm font-medium text-gray-700">Phone (Optional)</label>
                    <input
                        type="tel"
                        id="paid_to_phone"
                        wire:model="paid_to_phone"
                        maxlength="20"
                        placeholder="+880-1234-567890"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    @error('paid_to_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                <textarea
                    id="notes"
                    wire:model="notes"
                    maxlength="1000"
                    rows="4"
                    placeholder="Additional notes or details"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                ></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attachments --}}
            @if ($this->mediaIds)
                <div>
                    <label class="block text-sm font-medium text-gray-700">Attachments (Optional)</label>
                    <div class="mt-2 space-y-2">
                        @foreach ($this->mediaIds as $index => $id)
                            <div class="flex items-center justify-between rounded bg-gray-50 p-2">
                                <span class="text-sm text-gray-700">Attachment {{ $index + 1 }}</span>
                                <button
                                    type="button"
                                    wire:click="removeAttachment({{ $index }})"
                                    class="text-xs text-red-600 hover:text-red-800"
                                >
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <button
                type="button"
                @click="$dispatch('open-media-picker')"
                class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100"
            >
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Attachment
            </button>

            {{-- Form Actions --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-end gap-3">
                    <a
                        href="{{ route('admin.accounts.expenses.index') }}"
                        class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-md bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove>Submit {{ $category->name }}</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
