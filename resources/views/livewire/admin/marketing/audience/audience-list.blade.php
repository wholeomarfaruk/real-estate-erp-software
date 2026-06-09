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
    @teleport('body')
    <div x-data="{ drawerOpen: $wire.entangle('drawerOpen') }" x-show="drawerOpen" x-cloak
         class="fixed inset-0 z-9999 flex justify-end">

        {{-- Backdrop --}}
        <div @click="$wire.closeDrawer()" class="absolute inset-0 bg-black/40"></div>

        {{-- Panel --}}
        <div class="relative w-145 h-screen bg-white overflow-y-auto shadow-2xl flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-200 sticky top-0 bg-white z-10">
                <div class="font-semibold text-base" x-text="$wire.editingId ? 'Edit Audience' : 'New Audience'"></div>
                <button @click="$wire.closeDrawer()" class="text-gray-400 hover:text-gray-600">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 flex-1">
                <form wire:submit="save">
                    <div class="flex flex-col gap-4">

                        {{-- Name --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Audience Name <span class="text-red-500">*</span></label>
                            <input wire:model="fName" type="text" placeholder="e.g. Hot Leads Q2"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-gray-400">
                            @error('fName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Description</label>
                            <textarea wire:model="fDescription" rows="2" placeholder="Optional notes…"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-gray-400 resize-none"></textarea>
                        </div>

                        {{-- Type --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                @foreach(['dynamic'=>'Dynamic (auto-filter)','static'=>'Static (manual list)'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model.live="fType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold border-gray-200 bg-white text-gray-500 peer-checked:bg-accent peer-checked:text-white peer-checked:border-accent">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Dynamic filters --}}
                        <div x-show="$wire.fType === 'dynamic'" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Filter Criteria</div>
                            <div class="flex flex-col gap-3">

                                <div>
                                    <label class="block text-xs text-gray-500 mb-1.5">Lead Status</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost','cold'=>'Cold'] as $val=>$lbl)
                                        <label class="cursor-pointer">
                                            <input wire:model="fLeadStatus" type="checkbox" value="{{ $val }}" class="sr-only peer">
                                            <span class="inline-block px-2.5 py-1 rounded-full border text-xs font-medium border-gray-200 bg-white text-gray-500 peer-checked:bg-accent peer-checked:text-white peer-checked:border-accent">{{ $lbl }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Project</label>
                                        <select wire:model="fProjectId" class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg bg-white outline-none">
                                            <option value="">Any project</option>
                                            @foreach($projects as $proj)
                                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Lead Source</label>
                                        <select wire:model="fSourceId" class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg bg-white outline-none">
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
                                        class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg bg-white outline-none">
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model.live="fIncludeCustomers" type="checkbox" class="w-4 h-4 rounded accent-accent">
                                    <span class="text-xs text-gray-600">Include Customers</span>
                                </label>

                                <div x-show="$wire.fIncludeCustomers">
                                    <label class="block text-xs text-gray-500 mb-1">Customer Status</label>
                                    <select wire:model="fCustomerStatus" class="w-full px-3 py-2 text-xs border border-gray-200 rounded-lg bg-white outline-none">
                                        <option value="">Any status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <button type="button" wire:click="previewAudience" wire:loading.attr="disabled"
                                    class="w-full py-2 border border-dashed border-accent rounded-lg text-xs font-semibold text-accent bg-transparent cursor-pointer">
                                    <span wire:loading.remove wire:target="previewAudience">Preview Recipient Count</span>
                                    <span wire:loading wire:target="previewAudience">Counting…</span>
                                </button>
                                @if($previewCount > 0)
                                <div class="text-center text-sm font-semibold text-gray-600">~{{ number_format($previewCount) }} recipients</div>
                                @endif
                            </div>
                        </div>

                        {{-- Static member management --}}
                        <div x-show="$wire.fType === 'static'" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Members</span>
                                <span class="text-xs font-semibold text-gray-600">{{ count($staticMembers) }} added</span>
                            </div>

                            {{-- Search input --}}
                            <div class="relative mb-3">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                                <input wire:model.live.debounce.300ms="memberSearch" type="text"
                                    placeholder="Search leads by name, phone, lead no…"
                                    class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-white outline-none focus:border-gray-400">

                                {{-- Dropdown results --}}
                                @if(count($searchResults))
                                <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-20 overflow-hidden">
                                    @foreach($searchResults as $r)
                                    <button type="button" wire:click="addMember({{ $r['id'] }})"
                                        class="w-full text-left px-4 py-2.5 border-b border-gray-100 flex items-center justify-between gap-3 hover:bg-gray-50 cursor-pointer">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800">{{ $r['name'] }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5 font-mono">{{ $r['lead_no'] }} · {{ $r['phone'] }}</div>
                                        </div>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap"
                                              style="background:{{ $r['status_color'] }}1a;color:{{ $r['status_color'] }};">{{ $r['status'] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            {{-- Added members list --}}
                            @if(count($staticMembers))
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                @foreach($staticMembers as $m)
                                <div class="flex items-center justify-between gap-3 px-3 py-2.5 border-b border-gray-100 bg-white last:border-b-0">
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-800 truncate">{{ $m['name'] }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $m['lead_no'] }} · {{ $m['phone'] }}</div>
                                    </div>
                                    <button type="button" wire:click="removeMember({{ $m['id'] }})"
                                        class="shrink-0 text-gray-400 hover:text-red-600 text-base leading-none px-1 cursor-pointer bg-transparent border-0"
                                        title="Remove">×</button>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-5 text-xs text-gray-400">No members yet. Search leads above to add.</div>
                            @endif
                        </div>

                        {{-- Active --}}
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="fIsActive" type="checkbox" class="w-4 h-4 rounded accent-accent">
                            <span class="text-sm text-gray-600">Active</span>
                        </label>

                        {{-- Footer buttons --}}
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.closeDrawer()"
                                class="px-5 py-2 text-xs font-semibold border border-gray-200 rounded-lg bg-gray-50 text-gray-600 cursor-pointer">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-6 py-2 text-xs font-semibold bg-gray-900 text-white rounded-lg cursor-pointer">
                                <span wire:loading.remove wire:target="save">Save Audience</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
    @endteleport
</div>
