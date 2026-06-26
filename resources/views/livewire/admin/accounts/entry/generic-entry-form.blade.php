<div class="max-w-2xl mx-auto px-4 py-6">
    <style>
        :root { --gap: 0.75rem; --padding: 1rem; }
        .form-container { display: flex; flex-direction: column; gap: calc(var(--gap) * 1.5); }
        .form-group { display: flex; flex-direction: column; gap: 0.25rem; }
        .form-label { font-size: 13px; font-weight: 600; color: #333; }
        .form-input, .form-select, .form-textarea { padding: 0.625rem 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 2px rgba(0,102,204,0.1); }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--gap); }
        .form-full { grid-column: 1/-1; }
        .btn { padding: 0.625rem 1.25rem; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .btn-primary { background: #0066cc; color: white; }
        .btn-primary:hover { background: #0052a3; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .button-group { display: flex; gap: var(--gap); justify-content: flex-end; margin-top: 1rem; }
        .error-text { color: #d32f2f; font-size: 11px; margin-top: 0.25rem; }
    </style>

    <div class="form-container">
        <div>
            <h2 style="font-size: 20px; font-weight: 600; margin: 0 0 0.5rem 0;">{{ $entryType->name }}</h2>
            <p style="font-size: 12px; color: #666; margin: 0;">{{ $entryType->description }}</p>
        </div>

        <form wire:submit.prevent="save">
            <div class="form-row form-full">
                <div class="form-group">
                    <label class="form-label">Debit Account</label>
                    <select wire:model="debit_account_id" class="form-select">
                        <option value="">Select account...</option>
                        @foreach ($debitAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->code }})</option>
                        @endforeach
                    </select>
                    @error('debit_account_id') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Credit Account</label>
                    <select wire:model="credit_account_id" class="form-select">
                        <option value="">Select account...</option>
                        @foreach ($creditAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->code }})</option>
                        @endforeach
                    </select>
                    @error('credit_account_id') <span class="error-text">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row form-full">
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <input type="number" wire:model="amount" step="0.01" placeholder="0.00" class="form-input">
                    @error('amount') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" wire:model="date" class="form-input">
                    @error('date') <span class="error-text">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row form-full">
                <div class="form-group">
                    <label class="form-label">Method</label>
                    <select wire:model="method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                    @error('method') <span class="error-text">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Reference No.</label>
                    <input type="text" wire:model="reference_no" placeholder="Optional" class="form-input">
                </div>
            </div>

            <div class="form-row form-full">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" wire:model="name" placeholder="Payer/Payee name" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" wire:model="phone" placeholder="Contact number" class="form-input">
                </div>
            </div>

            <div class="form-group form-full">
                <label class="form-label">Notes</label>
                <textarea wire:model="notes" placeholder="Add any additional notes..." class="form-textarea" rows="3"></textarea>
                @error('notes') <span class="error-text">{{ $message }}</span> @enderror
            </div>

            <div class="button-group">
                <a href="{{ route('admin.account-entries.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit {{ $entryType->name }}</button>
            </div>
        </form>
    </div>
</div>
