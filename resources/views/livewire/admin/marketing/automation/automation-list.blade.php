<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'Automations', slug: 'marketing' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--mono:'IBM Plex Mono',monospace;font-family:'Inter',system-ui,sans-serif;color:var(--ink-1);background:var(--canvas);"
    class="min-h-screen">

    {{-- Header --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px;color:var(--ink-3);font-family:var(--mono);display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span>Marketing</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Automations</span>
            </div>
            <h1 style="font-size:24px;font-weight:600;letter-spacing:-.01em;margin:0;">Automation Engine</h1>
            <p style="margin-top:4px;font-size:13px;color:var(--ink-2);">Trigger automated messages on lead/customer events.</p>
        </div>
        @can('marketing.automation.create')
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white cursor-pointer border-0"
            style="background:var(--ink-1);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Automation
        </button>
        @endcan
    </div>

    <div style="padding:20px 24px 80px;">
        {{-- KPI --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--rule);border:1px solid var(--rule);border-radius:10px;overflow:hidden;margin-bottom:20px;">
            @foreach([['label'=>'Total','value'=>$kpi['total'],'fg'=>'var(--ink-1)'],['label'=>'Active','value'=>$kpi['active'],'fg'=>'#065F46'],['label'=>'Inactive','value'=>$kpi['inactive'],'fg'=>'var(--ink-3)']] as $s)
            <div style="background:var(--paper);padding:14px 18px;">
                <div style="font:600 10px 'Inter',sans-serif;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);">{{ $s['label'] }}</div>
                <div style="margin-top:5px;font:600 22px var(--mono);color:{{ $s['fg'] }};">{{ $s['value'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Table --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas);border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Name</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Trigger</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Action</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Template</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Delay</th>
                        <th style="padding:10px 16px;text-align:center;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Active</th>
                        <th style="padding:10px 16px;text-align:right;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($automations as $auto)
                    @php
                        $actionColors = ['send_sms'=>'background:#DBEAFE;color:#1D4ED8;','send_email'=>'background:#EDE9FE;color:#6D28D9;','send_both'=>'background:#D1FAE5;color:#065F46;'];
                    @endphp
                    <tr style="border-bottom:1px solid var(--rule);">
                        <td style="padding:12px 16px;">
                            <div style="font-weight:600;color:var(--ink-1);">{{ $auto->name }}</div>
                            @if($auto->description)
                            <div style="font-size:11px;color:var(--ink-3);margin-top:2px;">{{ Str::limit($auto->description,50) }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <div style="font:11px var(--mono);color:var(--ink-2);background:var(--canvas);padding:3px 7px;border-radius:4px;border:1px solid var(--rule);display:inline-block;">{{ $auto->trigger_label }}</div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $actionColors[$auto->action_type] ?? '' }}">{{ str_replace('_',' ',strtoupper($auto->action_type)) }}</span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div style="font-size:12px;color:var(--ink-2);">{{ $auto->template?->name ?? '—' }}</div>
                        </td>
                        <td style="padding:12px 16px;">
                            @if($auto->delay_minutes > 0)
                                @php
                                    $delay = $auto->delay_minutes >= 60
                                        ? round($auto->delay_minutes / 60, 1).' hr'
                                        : $auto->delay_minutes.' min';
                                @endphp
                            <span style="font:11px var(--mono);color:var(--ink-2);">+{{ $delay }}</span>
                            @else
                            <span style="font-size:11px;color:var(--ink-3);">Immediate</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:center;">
                            @can('marketing.automation.edit')
                            <button wire:click="toggle({{ $auto->id }})"
                                style="display:inline-flex;align-items:center;width:40px;height:22px;border-radius:11px;border:none;cursor:pointer;padding:2px;transition:background .2s;{{ $auto->status === 'active' ? 'background:var(--accent);justify-content:flex-end;' : 'background:#D1D5DB;justify-content:flex-start;' }}">
                                <span style="width:18px;height:18px;border-radius:50%;background:white;display:block;"></span>
                            </button>
                            @else
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $auto->status === 'active' ? 'background:#D1FAE5;color:#065F46;' : 'background:#F3F4F6;color:#374151;' }}">{{ $auto->status === 'active' ? 'On' : 'Off' }}</span>
                            @endcan
                        </td>
                        <td style="padding:12px 16px;text-align:right;">
                            <div class="flex justify-end gap-2">
                                @can('marketing.automation.edit')
                                <button wire:click="openEdit({{ $auto->id }})" style="padding:5px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;color:var(--ink-2);background:var(--canvas);cursor:pointer;">Edit</button>
                                @endcan
                                @can('marketing.automation.delete')
                                <button x-data="livewireConfirm" @click="confirmAction({id:{{ $auto->id }},method:'delete',title:'Delete automation?',text:'This cannot be undone.'})" style="padding:5px 10px;border:1px solid #FCA5A5;border-radius:6px;font:12px 'Inter',sans-serif;color:#991B1B;background:#FEF2F2;cursor:pointer;">Del</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:48px;text-align:center;color:var(--ink-3);">No automations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($automations->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--rule);background:var(--canvas);">{{ $automations->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Drawer --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute;inset:0;background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative;width:560px;height:100vh;background:var(--paper);overflow-y:auto;box-shadow:-4px 0 24px rgba(0,0,0,.12);">

            <div style="padding:20px 24px;border-bottom:1px solid var(--rule);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--paper);z-index:10;">
                <div style="font-weight:600;font-size:16px;" x-text="$wire.editingId ? 'Edit Automation' : 'New Automation'"></div>
                <button @click="$wire.closeDrawer()" style="background:none;border:none;cursor:pointer;color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:24px;">
                <form wire:submit="save">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input wire:model="fName" type="text" placeholder="e.g. Welcome on Lead Created"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                            @error('fName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Description</label>
                            <textarea wire:model="fDescription" rows="2" placeholder="Optional notes…"
                                style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;resize:none;box-sizing:border-box;"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Trigger Event <span class="text-red-500">*</span></label>
                            <select wire:model="fTriggerEvent" style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                @foreach(\App\Models\Automation::TRIGGER_EVENTS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('fTriggerEvent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Action Type <span class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                @foreach(['send_sms'=>'SMS','send_email'=>'Email','send_both'=>'Both'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model="fActionType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Template <span class="text-red-500">*</span></label>
                            <select wire:model="fTemplateId" style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                <option value="">Select template…</option>
                                @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ strtoupper($tpl->type) }})</option>
                                @endforeach
                            </select>
                            @error('fTemplateId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Delay After Trigger</label>
                            <div class="flex items-center gap-2">
                                <input wire:model="fDelayMinutes" type="number" min="0" placeholder="0"
                                    style="width:100px;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px var(--mono);background:var(--canvas);outline:none;">
                                <span style="font-size:12px;color:var(--ink-3);">minutes (0 = immediate)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                            <div class="flex gap-2">
                                @foreach(['active'=>'Active','inactive'=>'Inactive'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model="fStatus" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.closeDrawer()"
                                style="padding:9px 20px;border:1px solid var(--rule);border-radius:7px;font:600 12px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-2);cursor:pointer;">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="padding:9px 24px;background:var(--ink-1);color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                                <span wire:loading.remove wire:target="save">Save Automation</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
