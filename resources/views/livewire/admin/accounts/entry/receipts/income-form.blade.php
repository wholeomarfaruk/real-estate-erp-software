<div class="max-w-[700px] mx-auto px-4 py-6">
    <style>
        :root {
            --gap: 0.6rem;
            --padding: 0.9rem;
        }
        .form-container { background: #fff; border-radius: 6px; border: 1px solid #e0e0e0; }
        .form-header { padding: 1.2rem 1.5rem; border-bottom: 1px solid #f0f0f0; }
        .form-header h1 { font-size: 20px; font-weight: 600; margin: 0 0 0.25rem 0; }
        .form-header p { font-size: 12px; color: #777; margin: 0; }
        .form-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #333; margin-bottom: 0.35rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--gap); }
        .form-group-full { grid-column: 1 / -1; }
        .form-footer { padding: 1.2rem 1.5rem; border-top: 1px solid #f0f0f0; display: flex; gap: 0.75rem; justify-content: flex-end; }
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
        .btn-secondary:hover { background: #efefef; border-color: #999; }
        .btn-primary { background: #0066cc; color: white; }
        .btn-primary:hover { background: #0052a3; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .error-text { color: #d32f2f; font-size: 11px; margin-top: 0.2rem; }
        .breadcrumb { font-size: 11px; color: #999; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .breadcrumb a { color: #0066cc; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
    </style>

    <div class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Admin</a>
        <span>/</span>
        <a href="{{ route('admin.account-entries.index') }}">Account Entries</a>
        <span>/</span>
        <a href="{{ route('admin.account-entries.category', 'receipts') }}">Receipts</a>
        <span>/</span>
        <span>Income</span>
    </div>

    <form wire:submit="save" class="form-container">
        <div class="form-header">
            <h1>Record Income</h1>
            <p>Create an income receipt entry in your accounting system</p>
        </div>

        <div class="form-body">
            {{-- Payment Account --}}
            <div class="form-group">
                <label>Received In Account *</label>
                <select wire:model="debit_account_id" required>
                    <option value="">-- Select Account --</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
                @error('debit_account_id')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Income Account --}}
            <div class="form-group">
                <label>Income Account *</label>
                <select wire:model="credit_account_id" required>
                    <option value="">-- Select Account --</option>
                    @foreach ($incomeAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                    @endforeach
                </select>
                @error('credit_account_id')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            {{-- Amount and Date --}}
            <div class="form-row">
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" wire:model="amount" step="0.01" min="0" placeholder="0.00" required />
                    @error('amount')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" wire:model="date" required />
                    @error('date')
                        <div class="error-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Payment Method and Reference --}}
            <div class="form-row">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select wire:model="method">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="mobile_banking">Mobile Banking</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference No.</label>
                    <input type="text" wire:model="reference_no" placeholder="e.g., INV-001" />
                </div>
            </div>

            {{-- Source Details --}}
            <div class="form-row">
                <div class="form-group">
                    <label>Received From (Name)</label>
                    <input type="text" wire:model="name" placeholder="Customer or source name" />
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" wire:model="phone" placeholder="Contact number" />
                </div>
            </div>

            {{-- Notes --}}
            <div class="form-group form-group-full">
                <label>Notes</label>
                <textarea wire:model="notes" rows="3" placeholder="Additional details..."></textarea>
                @error('notes')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-footer">
            <a href="{{ route('admin.account-entries.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Submit Income Entry</span>
                <span wire:loading>Submitting...</span>
            </button>
        </div>
    </form>
</div>
