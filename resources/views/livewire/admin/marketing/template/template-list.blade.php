<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'Templates', slug: 'marketing' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--mono:'IBM Plex Mono',monospace;font-family:'Inter',system-ui,sans-serif;color:var(--ink-1);background:var(--canvas);"
    class="min-h-screen">

    {{-- Header --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px;color:var(--ink-3);font-family:var(--mono);display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span>Marketing</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Templates</span>
            </div>
            <h1 style="font-size:24px;font-weight:600;letter-spacing:-.01em;margin:0;">Communication Templates</h1>
            <p style="margin-top:4px;font-size:13px;color:var(--ink-2);">Reusable SMS & Email templates with smart variables.</p>
        </div>
        @can('marketing.template.create')
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white cursor-pointer border-0"
            style="background:var(--ink-1);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Template
        </button>
        @endcan
    </div>

    <div style="padding:20px 24px 80px;">

        {{-- KPI --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--rule);border:1px solid var(--rule);border-radius:10px;overflow:hidden;margin-bottom:20px;">
            @foreach([['label'=>'Total','value'=>$kpi['total'],'fg'=>'var(--ink-1)'],['label'=>'SMS','value'=>$kpi['sms'],'fg'=>'#1D4ED8'],['label'=>'Email','value'=>$kpi['email'],'fg'=>'#7C3AED'],['label'=>'Active','value'=>$kpi['active'],'fg'=>'#065F46']] as $s)
            <div style="background:var(--paper);padding:14px 18px;">
                <div style="font:600 10px 'Inter',sans-serif;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);">{{ $s['label'] }}</div>
                <div style="margin-top:5px;font:600 22px var(--mono);color:{{ $s['fg'] }};">{{ $s['value'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <div style="position:relative;flex:1;min-width:180px;">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-3);" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search templates…"
                    style="width:100%;padding:7px 10px 7px 32px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;box-sizing:border-box;">
            </div>
            <select wire:model.live="filterType" style="padding:7px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                <option value="all">All Types</option>
                <option value="sms">SMS</option>
                <option value="email">Email</option>
                <option value="both">Both</option>
            </select>
        </div>

        {{-- Table --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas);border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Name</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Type</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Variables</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px;text-align:right;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                    <tr style="border-bottom:1px solid var(--rule);" class="hover:bg-gray-50">
                        <td style="padding:12px 16px;">
                            <div style="font-weight:600;font-size:13px;color:var(--ink-1);">{{ $tpl->name }}</div>
                            @if($tpl->subject)
                            <div style="font:11px var(--mono);color:var(--ink-3);margin-top:2px;">{{ Str::limit($tpl->subject,50) }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @php $typeColors=['sms'=>'background:#DBEAFE;color:#1D4ED8;','email'=>'background:#EDE9FE;color:#6D28D9;','both'=>'background:#D1FAE5;color:#065F46;']; @endphp
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $typeColors[$tpl->type] ?? '' }}">{{ strtoupper($tpl->type) }}</span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="flex flex-wrap gap-1">
                                @foreach($tpl->variables ?? [] as $var)
                                <span style="padding:2px 6px;border-radius:4px;font:11px var(--mono);background:var(--canvas);color:var(--ink-2);border:1px solid var(--rule);">{{"{"}}{{ $var }}{{"}"}}</span>
                                @endforeach
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($tpl->is_active)
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#D1FAE5;color:#065F46;">Active</span>
                            @else
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;background:#F3F4F6;color:#374151;">Inactive</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:right;">
                            <div class="flex justify-end gap-2">
                                @can('marketing.template.edit')
                                <button wire:click="openEdit({{ $tpl->id }})" style="padding:5px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;color:var(--ink-2);background:var(--canvas);cursor:pointer;">Edit</button>
                                @endcan
                                @can('marketing.template.delete')
                                <button x-data="livewireConfirm" @click="confirmAction({id:{{ $tpl->id }},method:'delete',title:'Delete template?',text:'This cannot be undone.'})" style="padding:5px 10px;border:1px solid #FCA5A5;border-radius:6px;font:12px 'Inter',sans-serif;color:#991B1B;background:#FEF2F2;cursor:pointer;">Del</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="padding:48px;text-align:center;color:var(--ink-3);">No templates yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($templates->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--rule);background:var(--canvas);">{{ $templates->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Drawer --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute;inset:0;background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative;width:560px;height:100vh;background:var(--paper);overflow-y:auto;box-shadow:-4px 0 24px rgba(0,0,0,.12);">

            <div style="padding:20px 24px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--paper);z-index:10;">
                <div style="font-weight:600;font-size:16px;" x-text="$wire.editingId ? 'Edit Template' : 'New Template'"></div>
                <button @click="$wire.closeDrawer()" style="background:none;border:none;cursor:pointer;color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:24px;">
                <form wire:submit="save">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Template Name <span class="text-red-500">*</span></label>
                            <input wire:model="fName" type="text" placeholder="e.g. Welcome SMS"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                            @error('fName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                @foreach(['sms'=>'SMS','email'=>'Email','both'=>'Both'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model="fType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all
                                                 border-gray-200 bg-white text-gray-500
                                                 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">
                                        {{ $lbl }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="$wire.fType === 'email' || $wire.fType === 'both'">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Subject</label>
                            <input wire:model="fSubject" type="text" placeholder="Email subject line"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Body <span class="text-red-500">*</span></label>
                            <div style="font:11px var(--mono);color:var(--ink-3);margin-bottom:6px;">Use {"{"}name{"}"}, {"{"}phone{"}"}, {"{"}project{"}"}, {"{"}budget_range{"}"}, {"{"}occupation{"}"} as variables</div>
                            <textarea wire:model="fBody" rows="8" placeholder="Hi {name}, we have an exciting offer..."
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;resize:vertical;box-sizing:border-box;"></textarea>
                            @error('fBody') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="fIsActive" type="checkbox" class="w-4 h-4 rounded accent-[var(--accent)]">
                                <span class="text-sm text-gray-600">Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.closeDrawer()"
                                style="padding:9px 20px;border:1px solid var(--rule);border-radius:7px;font:600 12px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-2);cursor:pointer;">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="padding:9px 24px;background:var(--ink-1);color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                                <span wire:loading.remove wire:target="save">Save Template</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
