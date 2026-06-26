<div class="max-w-[900px] mx-auto px-4 py-6"><style>:root{--gap:.6rem}.fc{background:#fff;border:1px solid #e0e0e0;border-radius:6px}.fh{padding:1.2rem 1.5rem;border-bottom:1px solid #f0f0f0}.fh h1{font-size:20px;font-weight:600;margin:0 0 .25rem 0}.fh p{font-size:12px;color:#777;margin:0}.fb{padding:1.5rem}.fg{margin-bottom:1rem}.fg label{display:block;font-size:12px;font-weight:600;margin-bottom:.35rem}.fg input,.fg select{width:100%;padding:.6rem .8rem;border:1px solid #ddd;border-radius:4px;font-size:13px}.tbl{width:100%;border-collapse:collapse;margin-bottom:1rem}.tbl th{text-align:left;font-size:11px;font-weight:600;padding:.6rem;border-bottom:1px solid #ddd;background:#f9f9f9}.tbl td{padding:.6rem;border-bottom:1px solid #f0f0f0}.tbl input{width:100%;padding:.4rem .6rem;border:1px solid #ddd;border-radius:3px;font-size:12px}.tbl input:focus{outline:0;border-color:#0066cc}.tbl .btn-rm{background:#f5f5f5;color:#d32f2f;border:1px solid #ddd;padding:.4rem .8rem;font-size:11px;cursor:pointer;border-radius:3px}.tbl .btn-rm:hover{background:#ffe0e0}.ff{padding:1.2rem 1.5rem;border-top:1px solid #f0f0f0;display:flex;gap:.75rem;justify-content:space-between}.btn-add{background:#f5f5f5;border:1px solid #ddd;padding:.6rem 1rem;border-radius:4px;font-size:12px;cursor:pointer}.btn-add:hover{background:#efefef}.ff-right{display:flex;gap:.75rem}.btn{padding:.6rem 1.2rem;border:0;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer}.btn-s{background:#f5f5f5;border:1px solid #ddd}.btn-s:hover{background:#efefef}.btn-p{background:#0066cc;color:#fff}.btn-p:hover{background:#0052a3}.et{color:#d32f2f;font-size:11px;margin-top:.2rem}.tot{padding:.75rem;background:#f0f8ff;border:1px solid #bbeaff;border-radius:4px;margin-bottom:1rem;font-size:12px;display:grid;grid-template-columns:1fr 1fr;gap:1rem}</style>
    <form wire:submit="save" class="fc">
        <div class="fh"><h1>Opening Balance</h1><p>Set initial account balances for the accounting period</p></div>
        <div class="fb">
            <div class="fg"><label>As of Date *</label><input type="date" wire:model="date" required /></div>
            <div style="overflow-x:auto"><table class="tbl">
                <thead><tr><th>Account</th><th style="width:120px">Debit</th><th style="width:120px">Credit</th><th style="width:60px">Action</th></tr></thead>
                <tbody>
                @forelse ($lines as $index => $line)
                    <tr>
                        <td><select wire:model="lines.{{ $index }}.account_id" required>
                            <option value="">-- Select --</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                            @endforeach
                        </select></td>
                        <td><input type="number" wire:model="lines.{{ $index }}.debit" step="0.01" min="0" /></td>
                        <td><input type="number" wire:model="lines.{{ $index }}.credit" step="0.01" min="0" /></td>
                        <td><button type="button" class="tbl btn-rm" wire:click="removeLine({{ $index }})">Remove</button></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;color:#999">No lines added</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <button type="button" class="btn-add" wire:click="addLine">+ Add Line</button>
            <div class="tot">
                <div><strong>Total Debits:</strong> <span id="total-debit">0.00</span></div>
                <div><strong>Total Credits:</strong> <span id="total-credit">0.00</span></div>
            </div>
            <div class="fg"><label>Notes</label><input type="text" wire:model="notes" placeholder="Optional notes" /></div>
        </div>
        <div class="ff">
            <span></span>
            <div class="ff-right">
                <a href="{{ route('admin.account-entries.index') }}" class="btn btn-s">Cancel</a>
                <button type="submit" class="btn btn-p" wire:loading.attr="disabled"><span wire:loading.remove>Submit Opening Balance</span><span wire:loading>...</span></button>
            </div>
        </div>
    </form>
    <script>
        document.addEventListener('livewire:updated', updateTotals);
        function updateTotals() {
            const lines = @json($lines ?? []);
            let debitTotal = 0, creditTotal = 0;
            lines.forEach(line => {
                debitTotal += parseFloat(line.debit || 0);
                creditTotal += parseFloat(line.credit || 0);
            });
            document.getElementById('total-debit').textContent = debitTotal.toFixed(2);
            document.getElementById('total-credit').textContent = creditTotal.toFixed(2);
        }
        updateTotals();
    </script>
</div>
