{{-- Expense Create — creates a BankingPaymentRequest directly (no expenses table) --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: 'New Expense', slug: 'accounts' }">
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

/* tabs */
.exp-tabs{ display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px; }
.exp-tab{ font-family:inherit;font-size:13px;font-weight:500;padding:10px 18px;border-radius:10px;border:1px solid var(--rule);background:var(--paper);color:var(--muted);cursor:pointer;transition:all .12s; }
.exp-tab:hover{ border-color:var(--accent);color:var(--accent); }
.exp-tab.active{ background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(13,42,74,.18); }

/* card + fields */
.card{ background:var(--paper);border:1px solid var(--rule);border-radius:14px;overflow:hidden; }
.card-head{ padding:14px 20px;border-bottom:1px solid var(--rule);background:#fafafb;font-family:"Instrument Serif",Georgia,serif;font-size:18px; }
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
      <h2>New Expense</h2>
      <div class="sub">Creates a banking payment request — routed through the banking approval workflow.</div>
    </div>
    <a href="{{ route('admin.accounts.expenses.index') }}" class="btn">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back to list
    </a>
  </div>

  {{-- Expense type tabs (dynamic, from parent expense categories) --}}
  <div class="exp-tabs">
    @forelse($tabs as $tab)
      <button type="button" wire:click="selectTab({{ $tab->id }})"
        class="exp-tab {{ $parent_category_id === $tab->id ? 'active' : '' }}">
        {{ $tab->name }}
      </button>
    @empty
      <span style="font-size:12px;color:var(--muted);">No expense categories found. Seed transaction categories first.</span>
    @endforelse
  </div>

  {{-- Form --}}
  <div class="card">
    <div class="card-head">Expense Details</div>
    <div class="card-body">
      <div class="grid">

        {{-- Title --}}
        <div class="full">
          <label class="lbl">Title / Description *</label>
          <input type="text" wire:model="title" class="inp @error('title') err @enderror" placeholder="e.g. Cement purchase for foundation" />
          @error('title') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Category --}}
        <div>
          <label class="lbl">Category *</label>
          <select wire:model="transaction_category_id" class="inp @error('transaction_category_id') err @enderror">
            <option value="">— Select category —</option>
            @foreach($expenseCategories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
          @error('transaction_category_id') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Project / Supplier reference --}}
        @if($isProjectTab)
          <div>
            <label class="lbl">Project *</label>
            <select wire:model="reference_id" class="inp @error('reference_id') err @enderror">
              <option value="">— Select project —</option>
              @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
              @endforeach
            </select>
            @error('reference_id') <div class="err-msg">{{ $message }}</div> @enderror
          </div>
        @elseif($isSupplierTab)
          <div>
            <label class="lbl">Supplier *</label>
            <select wire:model="reference_id" class="inp @error('reference_id') err @enderror">
              <option value="">— Select supplier —</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
            @error('reference_id') <div class="err-msg">{{ $message }}</div> @enderror
          </div>
        @endif

        {{-- Amount --}}
        <div>
          <label class="lbl">Amount (BDT) *</label>
          <input type="number" step="0.01" min="0" wire:model="amount" class="inp @error('amount') err @enderror" placeholder="0.00" />
          @error('amount') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Date --}}
        <div>
          <label class="lbl">Date *</label>
          <input type="date" wire:model="date" class="inp @error('date') err @enderror" />
          @error('date') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Pay from bank --}}
        <div>
          <label class="lbl">Pay From (Bank / Cash) *</label>
          <select wire:model="bank_account_id" class="inp @error('bank_account_id') err @enderror">
            <option value="">— Select account —</option>
            @foreach($bankAccounts as $b)
              <option value="{{ $b->id }}">{{ $b->bank_name }} @if($b->ac_number)· {{ $b->ac_number }}@endif ({{ ucfirst($b->type) }})</option>
            @endforeach
          </select>
          @error('bank_account_id') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

        {{-- Notes --}}
        <div class="full">
          <label class="lbl">Notes</label>
          <textarea wire:model="notes" rows="2" class="inp" placeholder="Optional notes"></textarea>
          @error('notes') <div class="err-msg">{{ $message }}</div> @enderror
        </div>

      </div>

      <div class="foot">
        <a href="{{ route('admin.accounts.expenses.index') }}" class="btn">Cancel</a>
        <button type="button" wire:click="save" class="btn primary" wire:loading.attr="disabled" wire:target="save">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          <span wire:loading.remove wire:target="save">Create &amp; Send to Banking</span>
          <span wire:loading wire:target="save">Saving…</span>
        </button>
      </div>
    </div>
  </div>
</div>
</div>
