<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'Audiences', slug: 'marketing' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--mono:'IBM Plex Mono',monospace;font-family:'Inter',system-ui,sans-serif;color:var(--ink-1);background:var(--canvas);"
    class="min-h-screen">

    {{-- Header --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px;color:var(--ink-3);font-family:var(--mono);display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span>Marketing</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Audiences</span>
            </div>
            <h1 style="font-size:24px;font-weight:600;letter-spacing:-.01em;margin:0;">Audience Builder</h1>
            <p style="margin-top:4px;font-size:13px;color:var(--ink-2);">Segment leads and customers for targeted campaigns.</p>
        </div>
        @can('marketing.audience.create')
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white cursor-pointer border-0"
            style="background:var(--ink-1);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Audience
        </button>
        @endcan
    </div>

    <div style="padding:20px 24px 80px;">
        {{-- Search bar --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="position:relative;flex:1;">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-3);" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search audiences…"
                    style="width:100%;padding:7px 10px 7px 32px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;box-sizing:border-box;">
            </div>
        </div>

        {{-- Table --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas);border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Name</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Type</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Members</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px;text-align:right;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audiences as $aud)
                    <tr style="border-bottom:1px solid var(--rule);">
                        <td style="padding:12px 16px;">
                            <div style="font-weight:600;color:var(--ink-1);">{{ $aud->name }}</div>
                            @if($aud->description)
                            <div style="font-size:11px;color:var(--ink-3);margin-top:2px;">{{ Str::limit($aud->description,60) }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @if($aud->type === 'dynamic')
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#DBEAFE;color:#1D4ED8;">Dynamic</span>
                            @else
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#F3F4F6;color:#374151;">Static</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="flex items-center gap-2">
                                <span style="font:600 15px var(--mono);color:var(--ink-1);">{{ number_format($aud->member_count) }}</span>
                                @can('marketing.audience.edit')
                                <button wire:click="refresh({{ $aud->id }})" title="Refresh count" style="background:none;border:none;cursor:pointer;color:var(--ink-3);padding:2px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6"/><path d="M23 20v-6h-6"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($aud->is_active)
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#D1FAE5;color:#065F46;">Active</span>
                            @else
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#F3F4F6;color:#374151;">Inactive</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:right;">
                            <div class="flex justify-end gap-2">
                                @can('marketing.audience.edit')
                                <button wire:click="openEdit({{ $aud->id }})" style="padding:5px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;color:var(--ink-2);background:var(--canvas);cursor:pointer;">Edit</button>
                                @endcan
                                @can('marketing.audience.delete')
                                <button x-data="livewireConfirm" @click="confirmAction({id:{{ $aud->id }},method:'delete',title:'Delete audience?',text:'Campaigns using this audience will lose their target.'})" style="padding:5px 10px;border:1px solid #FCA5A5;border-radius:6px;font:12px 'Inter',sans-serif;color:#991B1B;background:#FEF2F2;cursor:pointer;">Del</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:48px;text-align:center;color:var(--ink-3);">No audiences yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($audiences->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--rule);background:var(--canvas);">{{ $audiences->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Drawer --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute;inset:0;background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative;width:580px;height:100vh;background:var(--paper);overflow-y:auto;box-shadow:-4px 0 24px rgba(0,0,0,.12);">

            <div style="padding:20px 24px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--paper);z-index:10;">
                <div style="font-weight:600;font-size:16px;" x-text="$wire.editingId ? 'Edit Audience' : 'New Audience'"></div>
                <button @click="$wire.closeDrawer()" style="background:none;border:none;cursor:pointer;color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:24px;">
                <form wire:submit="save">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Audience Name <span class="text-red-500">*</span></label>
                            <input wire:model="fName" type="text" placeholder="e.g. Hot Leads Q2"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                            @error('fName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Description</label>
                            <textarea wire:model="fDescription" rows="2" placeholder="Optional notes…"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;resize:none;box-sizing:border-box;"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                @foreach(['dynamic'=>'Dynamic (auto-filter)','static'=>'Static (manual list)'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model.live="fType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Dynamic filters --}}
                        <div x-show="$wire.fType === 'dynamic'" style="border:1px solid var(--rule);border-radius:8px;padding:16px;background:var(--canvas);">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Filter Criteria</div>

                            <div class="flex flex-col gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1.5">Lead Status</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost','cold'=>'Cold'] as $val=>$lbl)
                                        <label class="cursor-pointer">
                                            <input wire:model="fLeadStatus" type="checkbox" value="{{ $val }}" class="sr-only peer">
                                            <span class="inline-block px-2.5 py-1 rounded-full border text-xs font-medium transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Project</label>
                                        <select wire:model="fProjectId" style="width:100%;padding:7px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;background:var(--paper);color:var(--ink-1);outline:none;">
                                            <option value="">Any project</option>
                                            @foreach($projects as $proj)
                                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Lead Source</label>
                                        <select wire:model="fSourceId" style="width:100%;padding:7px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;background:var(--paper);color:var(--ink-1);outline:none;">
                                            <option value="">Any source</option>
                                            @foreach($sources as $src)
                                            <option value="{{ $src->id }}">{{ $src->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Min Budget (BDT)</label>
                                    <input wire:model="fBudgetMin" type="number" placeholder="e.g. 1000000"
                                        style="width:100%;padding:7px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;background:var(--paper);outline:none;box-sizing:border-box;">
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model.live="fIncludeCustomers" type="checkbox" class="w-4 h-4 rounded accent-[var(--accent)]">
                                    <span class="text-xs text-gray-600">Include Customers</span>
                                </label>

                                <div x-show="$wire.fIncludeCustomers">
                                    <label class="block text-xs text-gray-500 mb-1">Customer Status</label>
                                    <select wire:model="fCustomerStatus" style="width:100%;padding:7px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;background:var(--paper);color:var(--ink-1);outline:none;">
                                        <option value="">Any status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <button type="button" wire:click="previewAudience" wire:loading.attr="disabled"
                                    style="width:100%;padding:8px;border:1px dashed var(--accent);border-radius:6px;font:600 12px 'Inter',sans-serif;color:var(--accent);background:transparent;cursor:pointer;">
                                    <span wire:loading.remove wire:target="previewAudience">Preview Recipient Count</span>
                                    <span wire:loading wire:target="previewAudience">Counting…</span>
                                </button>
                                @if($previewCount > 0)
                                <div style="text-align:center;font:600 13px var(--mono);color:var(--ink-2);">~{{ number_format($previewCount) }} recipients</div>
                                @endif
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="fIsActive" type="checkbox" class="w-4 h-4 rounded accent-[var(--accent)]">
                            <span class="text-sm text-gray-600">Active</span>
                        </label>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.closeDrawer()"
                                style="padding:9px 20px;border:1px solid var(--rule);border-radius:7px;font:600 12px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-2);cursor:pointer;">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="padding:9px 24px;background:var(--ink-1);color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                                <span wire:loading.remove wire:target="save">Save Audience</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
