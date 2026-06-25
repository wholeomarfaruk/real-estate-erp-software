{{-- Project Expense Form — focused entry for project expenses with payment tracking --}}
<div class="prj-page" x-data x-init="
  $store.pageName = { name: 'New Project Expense', slug: 'accounts' };
  console.log('ProjectExpenseForm loaded', @this);
">
<style>
:root{
  --ink:#14181f; --ink-2:#2a2f3a; --muted:#6b7280; --muted-2:#9aa0a6;
  --rule:#e4e4e7; --rule-soft:#ececec; --paper:#fff; --canvas:#f6f6f7;
  --accent:#0d2a4a; --accent-soft:#eaf0f8;
  --ok:#1f6f43; --ok-bg:#e9f4ee; --ok-bd:#bfddc8; --danger:#8a1212;
}
.exp-wrap{ font-family:"Inter",system-ui,sans-serif; color:var(--ink); }
.exp-head{ display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px; }
.exp-head h2{ font-family:"Instrument Serif",Georgia,serif;font-weight:400;font-size:26px;margin:0; }
.exp-head .sub{ font-size:12px;color:var(--muted);margin-top:2px; }
.btn{ font-family:inherit;font-size:12.5px;font-weight:500;padding:9px 15px;border-radius:7px;border:1px solid var(--rule);background:var(--paper);color:var(--ink-2);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none; }
.btn:hover{ background:#fafafb; }
.btn.primary{ background:var(--accent);color:#fff;border-color:var(--accent); }
.btn.primary:hover{ background:#0a2240; }
.btn .ic{ width:14px;height:14px; }

/* card + fields */
.card{ background:var(--paper);border:1px solid var(--rule);border-radius:14px;overflow:hidden;margin-bottom:20px; }
.card-head{ padding:14px 20px;border-bottom:1px solid var(--rule);background:#fafafb;font-family:"Instrument Serif",Georgia,serif;font-size:16px;font-weight:500; }
.card-body{ padding:20px; }
.grid{ display:grid;grid-template-columns:1fr 1fr;gap:16px; }
.full{ grid-column:1 / -1; }
.lbl{ font-size:10.5px;letter-spacing:.5px;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:6px;display:block; }
.inp{ width:100%;font-family:inherit;font-size:13.5px;padding:10px 12px;border:1px solid var(--rule);border-radius:8px;background:var(--paper);color:var(--ink); }
.inp:focus{ outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft); }
.inp.err{ border-color:var(--danger); }
.err-msg{ font-size:11px;color:var(--danger);margin-top:4px; }
.foot{ display:flex;justify-content:flex-end;gap:10px;margin-top:18px; }
</style>

<div class="exp-wrap">
  {{-- Header --}}
  <div class="exp-head">
    <div>
      <h2>New Project Expense</h2>
      <div class="sub">Record a project-related expense and route it through banking approval.</div>
    </div>
    <a href="{{ route('admin.accounts.expenses.index') }}" class="btn">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to list
    </a>
  </div>

  {{-- EXPENSE DETAILS CARD --}}
  <div class="card">
    <div class="card-head">Expense Details</div>
    <div class="card-body">
      <div class="grid">

        {{-- Project --}}
        <div>
          <label class="lbl">Project *</label>
          <select wire:model="project_id" class="inp @error('project_id') err @enderror">
            <option value="">— Select project —</option>
            @foreach($projects as $proj)
              <option value="{{ $proj->id }}">{{ $proj->name }}</option>
            @endforeach
          </select>
          @error('project_id') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Expense Head --}}
        <div>
          <label class="lbl">Expense Head *</label>
          <select wire:model="expense_account_id" class="inp @error('expense_account_id') err @enderror">
            <option value="">— Select head —</option>
            @foreach($expenseAccounts as $acc)
              <option value="{{ $acc->id }}">{{ $acc->name }}</option>
            @endforeach
          </select>
          @error('expense_account_id') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Work Phase --}}
        <div>
          <label class="lbl">Work Phase (Optional)</label>
          <select wire:model="project_work_phase" class="inp @error('project_work_phase') err @enderror">
            <option value="">— No phase / Others —</option>
            @foreach($workPhases as $phase)
              <option value="{{ $phase['value'] }}">{{ $phase['label'] }}</option>
            @endforeach
          </select>
          @error('project_work_phase') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Title --}}
        <div>
          <label class="lbl">Title / Description *</label>
          <input type="text" wire:model="title" class="inp @error('title') err @enderror" placeholder="e.g. Labor cost for foundation work" />
          @error('title') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Amount --}}
        <div>
          <label class="lbl">Amount *</label>
          <input type="number" wire:model="amount" class="inp @error('amount') err @enderror" placeholder="0.00" step="0.01" min="0" />
          @error('amount') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Date --}}
        <div>
          <label class="lbl">Date *</label>
          <input type="date" wire:model="date" class="inp @error('date') err @enderror flatpickr-only-date" />
          @error('date') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

      </div>
    </div>
  </div>

  {{-- PAYMENT DETAILS CARD --}}
  <div class="card">
    <div class="card-head">Payment Information</div>
    <div class="card-body">
      <div class="grid">

        {{-- Pay From Account --}}
        <div>
          <label class="lbl">Pay From (Account) *</label>
          <select wire:model="payment_account_id" class="inp @error('payment_account_id') err @enderror">
            <option value="">— Select account —</option>
            @foreach($paymentAccounts as $acc)
              <option value="{{ $acc->id }}">
                ({{ ucfirst($acc->type->value) }}) {{ $acc->name }}
              </option>
            @endforeach
          </select>
          @error('payment_account_id') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Payment Method --}}
        <div>
          <label class="lbl">Payment Method *</label>
          <select wire:model="payment_method" class="inp @error('payment_method') err @enderror">
            @foreach($paymentMethods as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
          @error('payment_method') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Reference No (Cheque #, Receipt #, etc) --}}
        <div>
          <label class="lbl">Reference No (Optional)</label>
          <input type="text" wire:model="reference_no" class="inp @error('reference_no') err @enderror" placeholder="e.g. CHQ-12345 or RECEIPT-001" />
          @error('reference_no') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Paid To Name --}}
        <div>
          <label class="lbl">Paid To - Name (Optional)</label>
          <input type="text" wire:model="paid_to_name" class="inp @error('paid_to_name') err @enderror" placeholder="e.g. Ahmed Hassan" />
          @error('paid_to_name') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Paid To Phone --}}
        <div>
          <label class="lbl">Paid To - Phone (Optional)</label>
          <input type="tel" wire:model="paid_to_phone" class="inp @error('paid_to_phone') err @enderror" placeholder="e.g. 01711223344" />
          @error('paid_to_phone') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

      </div>
    </div>
  </div>

  {{-- NOTES & ATTACHMENTS CARD --}}
  <div class="card">
    <div class="card-head">Additional Information</div>
    <div class="card-body">
      <div class="grid">

        {{-- Notes --}}
        <div class="full">
          <label class="lbl">Notes (Optional)</label>
          <textarea wire:model="notes" class="inp @error('notes') err @enderror" placeholder="Any additional notes or memo..." rows="3" style="resize:vertical;"></textarea>
          @error('notes') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Attachments --}}
        <div class="full">
          <x-media-picker-field
            field="attachments"
            :value="$attachments"
            label="Attachments (Optional)"
            placeholder="Click to add receipts or supporting documents"
            :multiple="true"
            type="all"
            :required="false"
          />
        </div>

      </div>
    </div>
  </div>

  {{-- Error Summary --}}
  @if ($errors->any())
  <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;">
    <strong>Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;padding:0;">
      @foreach ($errors->all() as $error)
        <li style="margin:4px 0;">{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  {{-- Footer --}}
  <div class="foot">
    <a href="{{ route('admin.accounts.expenses.index') }}" class="btn">Cancel</a>
    <button type="button" wire:click="save" class="btn primary" wire:loading.attr="disabled" wire:target="save" style="min-width:140px;">
      <span wire:loading.remove wire:target="save">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Create Request
      </span>
      <span wire:loading wire:target="save">
        <svg class="ic" style="animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><path d="M12 1v6m0 6v6"/><path d="M4.22 4.22l4.24 4.24m3.08 3.08l4.24 4.24"/><path d="M1 12h6m6 0h6"/><path d="M4.22 19.78l4.24-4.24m3.08-3.08l4.24-4.24"/></svg>
        Submitting...
      </span>
    </button>
  </div>

  <style>
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>

</div>
</div>
