<div x-data x-init="$store.pageName = { name: 'Expense', slug: 'expenses' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a></li>
                <li>/</li>
                <li><a href="{{ route('admin.accounts.expenses.index') }}" wire:navigate class="hover:text-gray-800">Expenses</a></li>
                <li>/</li>
                <li class="text-gray-800">
                    {{ $expense ? $expense->expense_no : 'New Expense' }}
                </li>
            </ol>
        </nav>
        @if($expense)
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $expense->statusBadgeClass() }}">
            {{ $expense->statusLabel() }}
        </span>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ================================================================
             LEFT / MAIN COLUMN — form fields
             ================================================================ --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Expense details card --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Expense Details</h2>

                @php $isEditable = !$expense || $expense->isDraft(); @endphp

                <div class="mt-4 space-y-4">

                    {{-- Title --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Title <span class="text-red-500">*</span></label>
                        @if($isEditable)
                            <input type="text" wire:model.lazy="title" placeholder="e.g. Office electricity bill"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <p class="mt-1 text-sm font-medium text-gray-800">{{ $expense->title }}</p>
                        @endif
                    </div>

                    {{-- Date + Amount --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Date <span class="text-red-500">*</span></label>
                            @if($isEditable)
                                <input type="date" wire:model.lazy="date"
                                    class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <p class="mt-1 text-sm font-medium text-gray-800">{{ $expense->date->format('d M, Y') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Amount <span class="text-red-500">*</span></label>
                            @if($isEditable)
                                <input type="number" step="0.001" min="0" wire:model.lazy="amount" placeholder="0.00"
                                    class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-right text-sm focus:border-indigo-500 focus:outline-none">
                                @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @else
                                <p class="mt-1 text-sm font-semibold text-gray-800">{{ number_format($expense->amount, 2) }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Expense Account (DR) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600">
                            DR — Expense Account <span class="text-red-500">*</span>
                        </label>
                        @if($isEditable)
                            <select wire:model.live="expense_account_id"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">— Select account —</option>
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <p class="mt-1 text-sm font-medium text-gray-800">
                                {{ $expense->expenseAccount?->name ?? '—' }}
                            </p>
                        @endif
                    </div>

                    {{-- Transaction Category --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600">
                            Category <span class="text-red-500">*</span>
                        </label>
                        @if($isEditable)
                            <select wire:model.live="transaction_category_id"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">— Select category —</option>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('transaction_category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <p class="mt-1 text-sm font-medium text-gray-800">
                                {{ $expense->transactionCategory?->name ?? '—' }}
                            </p>
                        @endif
                    </div>

                    {{-- Bank Account (CR source) --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600">
                            Pay From (Bank / Cash) <span class="text-red-500">*</span>
                        </label>
                        @if($isEditable)
                            <select wire:model.live="bank_account_id"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">— Select bank / cash account —</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">
                                        {{ $bank->bank_name }}
                                        @if($bank->ac_number) ({{ $bank->ac_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_account_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @else
                            <p class="mt-1 text-sm font-medium text-gray-800">
                                {{ $expense->bankAccount?->bank_name ?? '—' }}
                            </p>
                        @endif
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Notes</label>
                        @if($isEditable)
                            <textarea wire:model.lazy="notes" rows="2" placeholder="Optional notes…"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                        @else
                            <p class="mt-1 text-sm text-gray-700">{{ $expense->notes ?: '—' }}</p>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Banking request status (shown after posting) --}}
            @if($expense && !$expense->isDraft() && $bankingRequest)
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Banking Payment Request</h2>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Request No</span>
                        <span class="font-mono text-xs text-gray-700">{{ $bankingRequest->request_no }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Amount</span>
                        <span class="font-semibold text-gray-800">{{ number_format($bankingRequest->amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Status</span>
                        @php
                            $badgeMap = [
                                'pending'   => 'bg-amber-50 text-amber-700 border border-amber-200',
                                'approved'  => 'bg-blue-50 text-blue-700 border border-blue-200',
                                'released'  => 'bg-violet-50 text-violet-700 border border-violet-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                'rejected'  => 'bg-red-50 text-red-700 border border-red-200',
                            ];
                            $badgeClass = $badgeMap[$bankingRequest->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                            {{ ucfirst($bankingRequest->status) }}
                        </span>
                    </div>
                </div>

                {{-- Workflow trail --}}
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <p class="text-xs font-medium text-gray-500 mb-2">Workflow</p>
                    <ol class="space-y-2 text-xs text-gray-500">
                        <li class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $bankingRequest->requested_by ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            <span>Requested by {{ $bankingRequest->requestedBy?->name ?? '—' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $bankingRequest->approved_by ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            <span>Approved {{ $bankingRequest->approved_by ? 'by ' . $bankingRequest->approvedBy?->name : '—' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $bankingRequest->released_by ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            <span>Released {{ $bankingRequest->released_by ? 'by ' . $bankingRequest->releasedBy?->name : '—' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $bankingRequest->completed_by ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                            <span>Completed {{ $bankingRequest->completed_by ? 'by ' . $bankingRequest->completedBy?->name : '—' }}</span>
                        </li>
                    </ol>
                </div>
            </div>
            @endif

            {{-- Transactions (shown after posting is completed) --}}
            @if($expense && $expense->isPosted() && $expense->transaction)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-5">
                <h2 class="text-sm font-semibold text-emerald-800 mb-3">Accounting Entries</h2>
                <div class="space-y-2 font-mono text-xs">
                    <div class="flex justify-between rounded-lg bg-white px-4 py-2 border border-emerald-100">
                        <span class="text-indigo-700">DR {{ $expense->expenseAccount?->name }}</span>
                        <span class="font-semibold text-gray-800">{{ number_format($expense->transaction->debit, 2) }}</span>
                    </div>
                    @if($expense->paymentAccount)
                    <div class="flex justify-between rounded-lg bg-white px-4 py-2 border border-emerald-100">
                        <span class="text-gray-500 pl-4">CR {{ $expense->paymentAccount->name }}</span>
                        <span class="font-semibold text-gray-800">{{ number_format($expense->amount, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ================================================================
             RIGHT COLUMN — actions & summary
             ================================================================ --}}
        <div class="space-y-6">

            {{-- Summary card --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Summary</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    @if($expense)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Expense No</dt>
                        <dd class="font-mono text-xs text-gray-700">{{ $expense->expense_no ?? '—' }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold text-gray-800">
                        <dt>Amount</dt>
                        <dd>{{ $amount ? number_format((float)$amount, 2) : '—' }}</dd>
                    </div>
                    @if($expense)
                    <div class="flex justify-between text-xs">
                        <dt class="text-gray-500">Created by</dt>
                        <dd class="text-gray-600">{{ $expense->creator?->name ?? '—' }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Action buttons --}}
            @if(!$expense)
                {{-- Create mode --}}
                <button type="button" wire:click="save"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save Draft
                </button>

            @elseif($expense->isDraft())
                {{-- Draft mode --}}
                <div class="space-y-3">
                    <button type="button" wire:click="update"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Save Changes
                    </button>

                    <button type="button" wire:click="post"
                        wire:confirm="Post this expense to the banking workflow? This cannot be undone."
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/>
                        </svg>
                        Post to Banking
                    </button>
                </div>

            @elseif($expense->isPending())
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Awaiting banking approval. Visit <a href="{{ route('admin.accounts.banking.index') }}" wire:navigate class="font-semibold underline">Banking Management</a> to process.
                </div>

            @elseif($expense->isPosted())
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Expense posted and accounting entries recorded.
                </div>
            @endif

        </div>
    </div>

</div>
