{{--
  Estimate Builder — create / edit a BOQ-style estimate.
  Props: $project, $materials (Product collection), $categories (TransactionCategory collection)
  Uses parent component state: $items[], $form_title, $form_estimate_date, $form_notes, $editingId
--}}
<div>
  {{-- Builder header --}}
  <div class="builder-head">
    <div>
      <h2>{{ $editingId ? 'Edit Estimate' : 'New Estimate' }}</h2>
      <span style="font-size:12px;color:var(--muted);">
        {{ $editingId ? 'Update line items, then save or submit.' : 'Build a BOQ with material, labour & overhead items.' }}
      </span>
    </div>
    <button wire:click="cancelForm" class="btn">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Back
    </button>
  </div>

  {{-- Estimate header fields --}}
  <div class="builder-card">
    <div class="bc-head">Estimate Details</div>
    <div class="bc-body">
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
        <div>
          <label class="field-label">Title *</label>
          <input type="text" wire:model="form_title" class="field-input @error('form_title') error @enderror" placeholder="e.g. Initial Estimate, Revised Estimate" />
          @error('form_title') <div class="field-err">{{ $message }}</div> @enderror
        </div>
        <div>
          <label class="field-label">Estimate Date *</label>
          <input type="date" wire:model="form_estimate_date" class="field-input flatpickr-only-date @error('form_estimate_date') error @enderror" />
          @error('form_estimate_date') <div class="field-err">{{ $message }}</div> @enderror
        </div>
      </div>
      <div style="margin-top:14px;">
        <label class="field-label">Notes</label>
        <textarea wire:model="form_notes" rows="2" class="field-input" placeholder="Optional remarks about this estimate version"></textarea>
        @error('form_notes') <div class="field-err">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>

  {{-- Line items --}}
  <div class="builder-card">
    <div class="bc-head" style="display:flex;align-items:center;justify-content:space-between;">
      <span>Bill of Quantities — Line Items</span>
      <span style="font-size:12px;color:var(--muted);font-family:'Inter',sans-serif;">{{ count($items) }} item(s)</span>
    </div>
    <div class="bc-body">
      @error('items') <div class="field-err" style="margin-bottom:10px;font-size:12px;">{{ $message }}</div> @enderror

      <div style="overflow-x:auto;">
        <table class="li-table">
          <thead>
            <tr>
              <th style="width:34px;">#</th>
              <th style="width:120px;">Cost Type</th>
              <th style="min-width:200px;">Item / Material</th>
              <th style="width:130px;">Work Phase</th>
              <th style="width:80px;">Unit *</th>
              <th class="right" style="width:90px;">Qty</th>
              <th class="right" style="width:110px;">Rate</th>
              <th class="right" style="width:120px;">Amount</th>
              <th style="width:40px;"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $i => $row)
              @php
                $qty = (float)($row['estimated_qty'] ?? 0);
                $rate = (float)($row['estimated_rate'] ?? 0);
                $amount = $qty * $rate;
                $isMaterial = ($row['cost_type'] ?? 'material') === 'material';
              @endphp
              <tr wire:key="item-{{ $i }}">
                <td style="padding-top:14px;color:var(--muted);font-size:11px;">{{ $i + 1 }}</td>

                {{-- Cost Type --}}
                <td>
                  <select wire:model.live="items.{{ $i }}.cost_type" class="li-input">
                    <option value="material">Material</option>
                    <option value="labour">Labour</option>
                    <option value="overhead">Overhead</option>
                    <option value="indirect">Indirect</option>
                  </select>
                </td>

                {{-- Item / Material --}}
                <td>
                  @if($isMaterial)
                    <select wire:model.live="items.{{ $i }}.material_id" class="li-input @error('items.'.$i.'.material_id') error @enderror">
                      <option value="">— Select material —</option>
                      @foreach($materials as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                      @endforeach
                    </select>
                    @error('items.'.$i.'.material_id') <div class="field-err">{{ $message }}</div> @enderror
                  @else
                    <select wire:model.live="items.{{ $i }}.transaction_category_id" class="li-input" style="margin-bottom:4px;">
                      <option value="">— Select category —</option>
                      @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                      @endforeach
                    </select>
                    <input type="text" wire:model="items.{{ $i }}.name" class="li-input @error('items.'.$i.'.name') error @enderror" placeholder="Item name" />
                    @error('items.'.$i.'.name') <div class="field-err">{{ $message }}</div> @enderror
                  @endif
                  {{-- Optional flag --}}
                  <label class="opt-check" style="margin-top:5px;">
                    <input type="checkbox" wire:model.live="items.{{ $i }}.is_optional" />
                    Optional item
                  </label>
                </td>

                {{-- Work Phase --}}
                <td>
                  <select wire:model="items.{{ $i }}.work_phase" class="li-input">
                    <option value="">— Phase —</option>
                    @foreach(\App\Enums\Projects\WorkPhase::cases() as $phase)
                      <option value="{{ $phase->value }}">{{ $phase->label() }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Unit --}}
                <td>
                  <input type="text" wire:model="items.{{ $i }}.unit" class="li-input @error('items.'.$i.'.unit') error @enderror" placeholder="Bag, Day, LS" />
                  @error('items.'.$i.'.unit') <div class="field-err">{{ $message }}</div> @enderror
                </td>

                {{-- Qty --}}
                <td>
                  <input type="number" step="0.01" min="0" wire:model.live="items.{{ $i }}.estimated_qty" class="li-input num @error('items.'.$i.'.estimated_qty') error @enderror" />
                </td>

                {{-- Rate --}}
                <td>
                  <input type="number" step="0.01" min="0" wire:model.live="items.{{ $i }}.estimated_rate" class="li-input num @error('items.'.$i.'.estimated_rate') error @enderror" />
                </td>

                {{-- Amount (computed) --}}
                <td class="li-amount">{{ number_format($amount, 2) }}</td>

                {{-- Remove --}}
                <td>
                  <button wire:click="removeItem({{ $i }})" class="li-remove" title="Remove item" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                </td>
              </tr>
              <tr wire:key="remark-{{ $i }}">
                <td></td>
                <td colspan="8" style="padding-top:0;border-bottom:1px solid var(--rule);">
                  <input type="text" wire:model="items.{{ $i }}.remarks" class="li-input" placeholder="Remarks (optional)" style="font-size:11px;" />
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <button wire:click="addItem" class="add-item-btn" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Line Item
      </button>

      {{-- Live grand total --}}
      <div style="display:flex;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:2px solid var(--ink);">
        <div style="text-align:right;">
          <div style="font-size:10.5px;letter-spacing:0.6px;text-transform:uppercase;color:var(--muted);font-weight:600;">Grand Total Estimated</div>
          <div style="font-family:'JetBrains Mono',ui-monospace,monospace;font-size:20px;font-weight:700;color:var(--ink);margin-top:4px;">
            BDT {{ number_format($this->formTotal, 2) }}
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Action buttons --}}
  <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
    <button type="button" wire:click="cancelForm" class="btn">Cancel</button>
    <button type="button" wire:click="saveEstimate('draft')" class="btn" wire:loading.attr="disabled" wire:target="saveEstimate">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      <span wire:loading.remove wire:target="saveEstimate">Save as Draft</span>
      <span wire:loading wire:target="saveEstimate">Saving…</span>
    </button>
    <button type="button" wire:click="saveEstimate('submitted')" class="btn primary" wire:loading.attr="disabled" wire:target="saveEstimate">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Save &amp; Submit
    </button>
  </div>
</div>
