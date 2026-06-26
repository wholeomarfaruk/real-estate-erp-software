<div class="max-w-6xl mx-auto px-4 py-6">
    <style>
        :root { --gap: 0.75rem; --padding: 1rem; }
        .container { display: flex; flex-direction: column; gap: calc(var(--gap) * 2); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .header h1 { font-size: 24px; font-weight: 600; margin: 0; }
        .btn { padding: 0.625rem 1.25rem; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
        .btn-primary { background: #0066cc; color: white; }
        .btn-primary:hover { background: #0052a3; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .table { width: 100%; border-collapse: collapse; background: white; border: 1px solid #ddd; border-radius: 4px; }
        .table th { background: #f5f5f5; padding: 0.75rem; text-align: left; font-size: 12px; font-weight: 600; border-bottom: 1px solid #ddd; }
        .table td { padding: 0.75rem; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .table tr:hover { background: #fafafa; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-warning { background: #fff3e0; color: #e65100; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex !important; align-items: center; justify-content: center; z-index: 1000; }
        .modal.hidden { display: none !important; }
        .modal-content { background: white; border-radius: 8px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { font-size: 18px; font-weight: 600; margin-bottom: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 1rem; }
        .form-label { font-size: 13px; font-weight: 600; color: #333; }
        .form-input, .form-select, .form-textarea { padding: 0.625rem 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 2px rgba(0,102,204,0.1); }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--gap); }
        .form-full { grid-column: 1/-1; }
        .modal-footer { display: flex; gap: var(--gap); justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #ddd; }
        .checkbox { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox input { cursor: pointer; }
        .actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #999; }
    </style>

    <div class="container">
        <div class="header">
            <div>
                <h1>Account Entry Types</h1>
                <p style="font-size: 12px; color: #666; margin: 0.5rem 0 0 0;">Manage dynamic entry types (locked entries are system-defined)</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="{{ route('admin.account-entries.index') }}" class="btn btn-secondary">← Back to Hub</a>
                <button type="button" wire:click="openCreateModal" class="btn btn-primary">+ Add New Entry Type</button>
            </div>
        </div>

        {{-- Dynamic Entries Table --}}
        @if ($entries->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Workflow</th>
                        <th>Status</th>
                        <th>Accounts</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td>
                                <strong>{{ $entry->name }}</strong>
                                <div style="font-size: 11px; color: #999; margin-top: 0.25rem;">{{ $entry->slug }}</div>
                            </td>
                            <td>{{ $entry->category->title }}</td>
                            <td>
                                <span class="badge" style="background: #e3f2fd; color: #1565c0;">
                                    {{ ucfirst(str_replace('_', ' ', $entry->workflow->value)) }}
                                </span>
                            </td>
                            <td>
                                @if ($entry->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-warning">Inactive</span>
                                @endif
                            </td>
                            <td style="font-size: 11px;">
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
                            </td>
                            <td style="text-align: right;">
                                <div class="actions">
                                    <button type="button" wire:click="openEditModal({{ $entry->id }})" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 12px;">Edit</button>
                                    <button type="button" wire:click="toggleActive({{ $entry->id }})" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 12px;">
                                        {{ $entry->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button type="button" wire:click="deleteEntry({{ $entry->id }})" onclick="return confirm('Delete this entry type?')" class="btn btn-danger" style="padding: 0.5rem 0.75rem; font-size: 12px;">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <p>No dynamic entry types yet.</p>
                <p style="font-size: 12px;">Click "Add New Entry Type" to create one.</p>
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if ($showCreateModal || $showEditModal)
    <div class="modal">
        <div class="modal-content">
            <div class="modal-header">
                {{ $editingId ? 'Edit Entry Type' : 'Create New Entry Type' }}
            </div>

            <form wire:submit.prevent="save">
                <div class="form-row form-full">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g., Custom Receipt" class="form-input">
                        @error('name') <span style="color: #d32f2f; font-size: 11px;">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug *</label>
                        <input type="text" wire:model="slug" placeholder="e.g., custom-receipt" class="form-input" {{ $editingId ? 'disabled' : '' }}>
                        @error('slug') <span style="color: #d32f2f; font-size: 11px;">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-group form-full">
                    <label class="form-label">Description</label>
                    <textarea wire:model="description" placeholder="What does this entry type do?" class="form-textarea" rows="2"></textarea>
                    @error('description') <span style="color: #d32f2f; font-size: 11px;">{{ $message }}</span> @enderror
                </div>

                <div class="form-row form-full">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select wire:model="category_key" class="form-select">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->key }}">{{ $cat->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Workflow *</label>
                        <select wire:model="workflow" class="form-select">
                            <option value="banking_request">Banking Request</option>
                            <option value="direct_ledger">Direct Ledger</option>
                            <option value="posting_engine">Posting Engine</option>
                        </select>
                    </div>
                </div>

                @if ($workflow === 'posting_engine')
                    <div class="form-group form-full">
                        <label class="form-label">Accounting Event Key</label>
                        <input type="text" wire:model="accounting_event_key" placeholder="e.g., expense.payment" class="form-input">
                        <div style="font-size: 11px; color: #999; margin-top: 0.25rem;">Available: expense.payment, hrm.salary_payment, property.rent_collection, etc.</div>
                    </div>
                @endif

                <div class="form-row form-full">
                    <div class="form-group">
                        <label class="form-label">Transaction Type</label>
                        <input type="text" wire:model="transaction_type" placeholder="e.g., custom_receipt" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Permission</label>
                        <input type="text" wire:model="permission" placeholder="e.g., accounts.entry.receipts.create" class="form-input">
                    </div>
                </div>

                <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <div style="font-size: 12px; font-weight: 600; margin-bottom: 0.75rem;">Debit Account Source</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Feature Type</label>
                            <select wire:model="debit_feature_type" class="form-select">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Group</label>
                            <select wire:model="debit_account_group" class="form-select">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Type</label>
                        <input type="text" wire:model="debit_account_type" placeholder="e.g., cash,bank,mfs" class="form-input">
                        <div style="font-size: 11px; color: #999; margin-top: 0.25rem;">Comma-separated: cash, bank, mfs, wallet</div>
                    </div>
                </div>

                <div style="background: #f5f5f5; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <div style="font-size: 12px; font-weight: 600; margin-bottom: 0.75rem;">Credit Account Source</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Feature Type</label>
                            <select wire:model="credit_feature_type" class="form-select">
                                <option value="">None</option>
                                @foreach ($features as $feature)
                                    <option value="{{ $feature->key }}">{{ $feature->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Group</label>
                            <select wire:model="credit_account_group" class="form-select">
                                <option value="">None</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Type</label>
                        <input type="text" wire:model="credit_account_type" placeholder="e.g., cash,bank" class="form-input">
                    </div>
                </div>

                <div class="form-row form-full">
                    <div class="checkbox">
                        <input type="checkbox" wire:model="is_active" id="is_active">
                        <label for="is_active" style="margin: 0; cursor: pointer;">Active</label>
                    </div>
                    <div class="checkbox">
                        <input type="checkbox" wire:model="is_visible" id="is_visible">
                        <label for="is_visible" style="margin: 0; cursor: pointer;">Visible</label>
                    </div>
                </div>

                <div class="form-group form-full">
                    <label class="form-label">Sort Order</label>
                    <input type="number" wire:model="sort_order" class="form-input">
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="closeModals" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Update' : 'Create' }} Entry Type</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
