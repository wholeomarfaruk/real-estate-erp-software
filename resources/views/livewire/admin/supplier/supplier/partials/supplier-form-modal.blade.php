{{-- ════════════════════════════════════════════════════════════════
     SUPPLIER FORM MODAL — shared by the supplier list and detail pages.
     Requires the host Livewire component to use InteractsWithSupplierForm
     (provides: $modalOpen, $editMode, $nextCode, $documents, $form, save,
     closeModal) and the WithMediaPicker trait.
     ════════════════════════════════════════════════════════════════ --}}
@canany(['supplier.create', 'supplier.edit'])
<x-modal wire:model="modalOpen" maxWidth="2xl">
    <div class="su-modal-inner" role="dialog" aria-modal="true" aria-labelledby="supModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="supModalTitle">{{ $editMode ? 'Edit supplier' : 'New supplier' }}</h3>
                <div class="sub">
                    @if($editMode)
                        Editing supplier record
                    @else
                        Code auto-generated · SUP-{{ str_pad($nextCode, 6, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
            </div>
            <button class="close" wire:click="closeModal" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="modal-body">

            {{-- Basic --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21V12h6v9"/></svg></span>Basic information</h4>
                    <span class="hint">required *</span>
                </div>
                <div class="grid-2">
                    <div class="span-2">
                        <label class="field-label">Supplier name <span class="req">*</span></label>
                        <input class="input" wire:model="form.name" placeholder="e.g. Meghna Cement & Aggregates Ltd." />
                        @error('form.name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    @if(!$editMode)
                    <div>
                        <label class="field-label">Code</label>
                        <input class="input mono" value="SUP-{{ str_pad($nextCode, 6, '0', STR_PAD_LEFT) }}" readonly />
                    </div>
                    @endif
                    <div>
                        <label class="field-label">Status</label>
                        <div class="seg-status">
                            <input type="radio" id="ss_active"   value="active"   wire:model="form.status" />
                            <label for="ss_active" class="active"><span class="dot"></span>Active</label>
                            <input type="radio" id="ss_inactive" value="inactive" wire:model="form.status" />
                            <label for="ss_inactive" class="inactive"><span class="dot"></span>Inactive</label>
                            <input type="radio" id="ss_blocked"  value="blocked"  wire:model="form.status" />
                            <label for="ss_blocked" class="blocked"><span class="dot"></span>Blocked</label>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Contact --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>Contact</h4>
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Contact person</label>
                        <input class="input" wire:model="form.contact_person" placeholder="Full name" />
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input class="input" type="email" wire:model="form.email" placeholder="accounts@supplier.com" />
                        @error('form.email') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label">Phone <span class="req">*</span></label>
                        <input class="input mono" wire:model="form.phone" placeholder="+880 1XXX XXXXXX" />
                        @error('form.phone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label">Alternate phone</label>
                        <input class="input mono" wire:model="form.alternate_phone" placeholder="optional" />
                    </div>
                    <div class="span-2">
                        <label class="field-label">Address</label>
                        <textarea class="textarea" wire:model="form.address" placeholder="House / Road / Area, City"></textarea>
                    </div>
                </div>
            </section>

            {{-- Compliance --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/></svg></span>Compliance</h4>
                    <span class="hint">tax & licence</span>
                </div>
                <div class="grid-3">
                    <div>
                        <label class="field-label">Trade licence no.</label>
                        <input class="input mono" wire:model="form.trade_license_no" placeholder="TRAD/2024/…" />
                    </div>
                    <div>
                        <label class="field-label">TIN no.</label>
                        <input class="input mono" wire:model="form.tin_no" />
                    </div>
                    <div>
                        <label class="field-label">BIN no.</label>
                        <input class="input mono" wire:model="form.bin_no" />
                    </div>
                </div>
            </section>

            {{-- Documents via media picker — stores array of file IDs --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>Documents</h4>
                    <span class="hint">json: [ file ids ]</span>
                </div>
                <x-media-picker-field
                    field="documents"
                    :value="$documents"
                    label="Attached Documents"
                    placeholder="Select documents"
                    :multiple="true"
                    type="all"
                    :required="false"
                />
            </section>

            {{-- Notes --}}
            <section class="section">
                <div class="section-title"><h4>Notes</h4></div>
                <textarea class="textarea" wire:model="form.notes" placeholder="Internal notes about this supplier…" style="min-height:64px;"></textarea>
            </section>

        </div>

        <footer class="modal-foot">
            <span class="note">Fields marked * are required</span>
            <div class="right">
                <button class="btn" type="button" wire:click="closeModal">Cancel</button>
                <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $editMode ? 'Update supplier' : 'Save supplier' }}</span>
                    <span wire:loading wire:target="save">{{ $editMode ? 'Updating…' : 'Saving…' }}</span>
                </button>
            </div>
        </footer>
    </div>
</x-modal>
@endcanany
