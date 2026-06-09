<div
    x-data="{
        drawerOpen: $wire.entangle('drawerOpen'),
        formStep: $wire.entangle('formStep'),
        fStatus: $wire.entangle('fStatus'),
        audienceModalOpen: $wire.entangle('audienceModalOpen'),
    }"
    x-init="$store.pageName = { name: 'Leads', slug: 'leads' }"
    style="
        --paper:#FCFBF7; --canvas:#F2EFE7; --ink-1:#1A1814; --ink-2:#5C5648;
        --ink-3:#9B9686; --rule:#EAE5D9; --accent:#1F3A68; --mono:'IBM Plex Mono',ui-monospace,monospace;
        --won-bg:#D1FAE5; --won-fg:#065F46;
        --lost-bg:#FEE2E2; --lost-fg:#991B1B;
        --new-bg:#EFF6FF; --new-fg:#1E40AF;
        --hot-bg:#FEF3C7; --hot-fg:#92400E;
        font-family:'Inter',system-ui,sans-serif; color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen">

    {{-- ─── PAGE HEADER ──────────────────────────────────────────────────────── --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:6px;">
                <span>CRM</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Leads</span>
            </div>
            <h1 style="font-size:24px; font-weight:600; letter-spacing:-.01em; margin:0;">Leads</h1>
            <p style="margin-top:4px; font-size:13px; color:var(--ink-2);">Track, qualify and convert prospects into customers.</p>
        </div>
        <div class="flex gap-2">
            @can('crm.lead.create')
            <button wire:click="openCreate"
                style="background:var(--ink-1); color:var(--paper); border:none; padding:8px 16px; border-radius:7px;
                       font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New Lead
            </button>
            @endcan
        </div>
    </div>

    <div style="padding:20px 24px 80px;">

        {{-- ─── KPI STRIP ─────────────────────────────────────────────────────── --}}
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1px; background:var(--rule);
                    border:1px solid var(--rule); border-radius:10px; overflow:hidden; margin-bottom:20px;">
            @foreach([
                ['label'=>'Total','value'=>$kpi['total'],'sub'=>'all leads','fg'=>'var(--ink-1)'],
                ['label'=>'New','value'=>$kpi['new'],'sub'=>'fresh leads','fg'=>'#1E40AF'],
                ['label'=>'Qualified','value'=>$kpi['qualified'],'sub'=>'in pipeline','fg'=>'#7C3AED'],
                ['label'=>'Won','value'=>$kpi['won'],'sub'=>'converted','fg'=>'#065F46'],
                ['label'=>'Lost','value'=>$kpi['lost'],'sub'=>'closed lost','fg'=>'#991B1B'],
            ] as $stat)
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter',sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">{{ $stat['label'] }}</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:{{ $stat['fg'] }}; font-variant-numeric:tabular-nums;">{{ $stat['value'] }}</div>
                <div style="margin-top:2px; font:11px var(--mono); color:var(--ink-3);">{{ $stat['sub'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- ─── FILTERS ───────────────────────────────────────────────────────── --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px;
                    padding:10px 14px; display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">

            <div style="position:relative; flex:1; min-width:200px;">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-3);"
                     width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, phone, lead no…"
                    style="width:100%; padding:7px 10px 7px 32px; border:1px solid var(--rule); border-radius:7px;
                           font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
            </div>

            <select wire:model.live="filterStatus"
                style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif;
                       background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="site_visit">Site Visit</option>
                <option value="negotiation">Negotiation</option>
                <option value="won">Won</option>
                <option value="lost">Lost</option>
            </select>

            <select wire:model.live="filterSource"
                style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif;
                       background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Sources</option>
                @foreach($sources as $src)
                <option value="{{ $src->id }}">{{ $src->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterAssigned"
                style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif;
                       background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Assigned</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ─── TABLE ─────────────────────────────────────────────────────────── --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas); border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Lead</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Contact</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Source</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Budget</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Assigned</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Score</th>
                        <th style="padding:10px 16px; text-align:right; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr style="border-bottom:1px solid var(--rule);" class="hover:bg-gray-50">
                        <td style="padding:12px 16px;">
                            <a href="{{ route('admin.crm.leads.show', $lead->id) }}"
                               style="font-weight:600; color:var(--accent); text-decoration:none; font-size:13px;">
                                {{ $lead->name }}
                            </a>
                            <div style="font:11px var(--mono); color:var(--ink-3); margin-top:2px;">{{ $lead->lead_no }}</div>
                        </td>
                        <td style="padding:12px 16px;">
                            <div style="font-size:13px;">{{ $lead->phone }}</div>
                            @if($lead->email)
                            <div style="font:11px var(--mono); color:var(--ink-3); margin-top:2px;">{{ $lead->email }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @if($lead->source)
                            <span style="display:inline-flex; align-items:center; gap:5px;">
                                <span style="width:8px; height:8px; border-radius:50%; background:{{ $lead->source->color }}; display:inline-block;"></span>
                                <span style="font-size:12px; color:var(--ink-2);">{{ $lead->source->name }}</span>
                            </span>
                            @else
                            <span style="font-size:12px; color:var(--ink-3);">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px; font:12px var(--mono); color:var(--ink-2);">
                            @if($lead->budget_min || $lead->budget_max)
                                ৳{{ number_format($lead->budget_min ?? 0) }} – {{ number_format($lead->budget_max ?? 0) }}
                            @else
                                <span style="color:var(--ink-3);">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @if($lead->assignedUser)
                            <span style="font-size:12px; color:var(--ink-2);">{{ $lead->assignedUser->name }}</span>
                            @else
                            <span style="font-size:12px; color:var(--ink-3);">Unassigned</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @php
                                $statusStyles = [
                                    'new'         => 'background:#EFF6FF; color:#1E40AF;',
                                    'contacted'   => 'background:#F0FDF4; color:#166534;',
                                    'qualified'   => 'background:#F5F3FF; color:#5B21B6;',
                                    'site_visit'  => 'background:#FFFBEB; color:#92400E;',
                                    'negotiation' => 'background:#FFF1F2; color:#9F1239;',
                                    'won'         => 'background:#D1FAE5; color:#065F46;',
                                    'lost'        => 'background:#FEE2E2; color:#991B1B;',
                                ];
                                $statusLabels = [
                                    'new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified',
                                    'site_visit'=>'Site Visit','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost',
                                ];
                            @endphp
                            <span style="padding:3px 9px; border-radius:20px; font:600 10px 'Inter',sans-serif; letter-spacing:.04em;
                                         {{ $statusStyles[$lead->status] ?? 'background:#F3F4F6; color:#374151;' }}">
                                {{ $statusLabels[$lead->status] ?? $lead->status }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div style="display:flex; align-items:center; gap:6px;">
                                <div style="flex:1; height:5px; background:var(--rule); border-radius:3px; max-width:60px;">
                                    <div style="height:5px; border-radius:3px; width:{{ $lead->score }}%;
                                                background:{{ $lead->score >= 70 ? '#10B981' : ($lead->score >= 40 ? '#F59E0B' : '#EF4444') }};"></div>
                                </div>
                                <span style="font:600 11px var(--mono); color:var(--ink-2);">{{ $lead->score }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <a href="{{ route('admin.crm.leads.show', $lead->id) }}"
                                   style="padding:5px 10px; border:1px solid var(--rule); border-radius:6px; font:12px 'Inter',sans-serif;
                                          color:var(--ink-2); text-decoration:none; background:var(--canvas);">
                                    View
                                </a>
                                @can('crm.lead.edit')
                                <button wire:click="openEdit({{ $lead->id }})"
                                    style="padding:5px 10px; border:1px solid var(--rule); border-radius:6px; font:12px 'Inter',sans-serif;
                                           color:var(--ink-2); background:var(--canvas); cursor:pointer;">
                                    Edit
                                </button>
                                @endcan
                                @can('marketing.audience.edit')
                                <button wire:click="openAudienceModal({{ $lead->id }})"
                                    style="padding:5px 10px; border:1px solid var(--rule); border-radius:6px; font:12px 'Inter',sans-serif;
                                           color:var(--accent); background:var(--canvas); cursor:pointer;"
                                    title="Add to Audience">
                                    + Audience
                                </button>
                                @endcan
                                @can('crm.lead.delete')
                                <button x-data="livewireConfirm"
                                    @click="confirmAction({ id:{{ $lead->id }}, method:'deleteLead', title:'Delete lead?', text:'This cannot be undone.' })"
                                    style="padding:5px 10px; border:1px solid #FCA5A5; border-radius:6px; font:12px 'Inter',sans-serif;
                                           color:#991B1B; background:#FEF2F2; cursor:pointer;">
                                    Del
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:48px; text-align:center; color:var(--ink-3); font-size:14px;">
                            No leads found. <button wire:click="openCreate" style="background:none; border:none; color:var(--accent); cursor:pointer; font-size:14px; text-decoration:underline;">Create your first lead.</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($leads->hasPages())
            <div style="padding:12px 16px; border-top:1px solid var(--rule); background:var(--canvas);">
                {{ $leads->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- ─── ADD TO AUDIENCE MODAL ─────────────────────────────────────────────── --}}
    <x-modal wire:model="audienceModalOpen" maxWidth="md">
        <div style="font-family:'Inter',system-ui,sans-serif;color:#1A1814;">
            {{-- Header --}}
            <div style="padding:18px 20px;border-bottom:1px solid #EAE5D9;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-weight:600;font-size:15px;">Add to Audience</div>
                    <div style="font-size:12px;color:#9B9686;margin-top:2px;">{{ $audienceLeadName }}</div>
                </div>
                <button @click="$wire.closeAudienceModal()" style="background:none;border:none;cursor:pointer;color:#9B9686;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Audience list --}}
            <div style="max-height:340px;overflow-y:auto;">
                @forelse($staticAudiences as $aud)
                <label style="display:flex;align-items:center;gap:12px;padding:11px 20px;border-bottom:1px solid #EAE5D9;cursor:pointer;"
                       onmouseover="this.style.background='#F2EFE7'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox"
                           wire:model="selectedAudienceIds"
                           value="{{ $aud->id }}"
                           style="width:15px;height:15px;accent-color:#1F3A68;cursor:pointer;flex-shrink:0;">
                    <span style="flex:1;font-size:13px;font-weight:500;">{{ $aud->name }}</span>
                    <span style="font:11px 'IBM Plex Mono',monospace;color:#9B9686;white-space:nowrap;">{{ $aud->members_count }} members</span>
                </label>
                @empty
                <div style="padding:32px 20px;text-align:center;font-size:13px;color:#9B9686;">
                    No active static audiences found.<br>
                    <a href="{{ route('admin.marketing.audiences.index') }}" style="color:#1F3A68;font-size:12px;">Create one →</a>
                </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div style="padding:14px 20px;border-top:1px solid #EAE5D9;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" @click="$wire.closeAudienceModal()"
                    style="padding:8px 18px;border:1px solid #EAE5D9;border-radius:7px;font:600 12px 'Inter',sans-serif;background:#F2EFE7;color:#5C5648;cursor:pointer;">
                    Cancel
                </button>
                <button type="button" wire:click="addToAudiences" wire:loading.attr="disabled"
                    style="padding:8px 20px;background:#1F3A68;color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                    <span wire:loading.remove wire:target="addToAudiences">Add to Selected</span>
                    <span wire:loading wire:target="addToAudiences">Adding…</span>
                </button>
            </div>
        </div>
    </x-modal>

    {{-- ─── CREATE / EDIT DRAWER ──────────────────────────────────────────────── --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute; inset:0; background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative; width:520px; height:100vh; background:var(--paper); overflow-y:auto; box-shadow:-4px 0 24px rgba(0,0,0,.12);">

            {{-- Drawer header --}}
            <div style="padding:20px 24px; border-bottom:1px solid var(--rule); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:var(--paper); z-index:10;">
                <div>
                    <div style="font-weight:600; font-size:16px;" x-text="$wire.editingId ? 'Edit Lead' : 'New Lead'"></div>
                    <div style="font-size:12px; color:var(--ink-3); margin-top:2px;">Step <span x-text="formStep"></span> of 3</div>
                </div>
                <button @click="$wire.closeDrawer()" style="background:none; border:none; cursor:pointer; color:var(--ink-3); padding:4px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Step tabs --}}
            <div style="padding:16px 24px 0; display:flex; gap:8px; border-bottom:1px solid var(--rule);">
                @foreach([1=>'Basic Info', 2=>'Social & Extra', 3=>'Notes & Files'] as $step => $label)
                <button @click="$wire.set('formStep', {{ $step }})"
                    style="padding:6px 14px; border-radius:6px 6px 0 0; font:500 12px 'Inter',sans-serif; cursor:pointer; border:1px solid;
                           border-bottom:none; margin-bottom:-1px;"
                    :style="formStep == {{ $step }}
                        ? 'background:var(--paper); border-color:var(--rule); color:var(--ink-1);'
                        : 'background:var(--canvas); border-color:transparent; color:var(--ink-3);'">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            <div style="padding:24px;">
                <form wire:submit="saveLead">

                    {{-- STEP 1: Basic Info --}}
                    <div x-show="formStep == 1">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <div style="grid-column:1/-1;">
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Name <span style="color:#EF4444;">*</span></label>
                                <input wire:model="fName" type="text" placeholder="Full name"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                @error('fName') <p style="color:#EF4444; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Phone <span style="color:#EF4444;">*</span></label>
                                <input wire:model="fPhone" type="text" placeholder="+880..."
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                @error('fPhone') <p style="color:#EF4444; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Email</label>
                                <input wire:model="fEmail" type="email" placeholder="email@example.com"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Lead Source</label>
                                <select wire:model="fLeadSourceId"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                    <option value="">— Select source —</option>
                                    @foreach($sources as $src)
                                    <option value="{{ $src->id }}">{{ $src->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Project</label>
                                <select wire:model="fProjectId"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                    <option value="">— Select project —</option>
                                    @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Assigned To</label>
                                <select wire:model="fAssignedTo"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                    <option value="">— Unassigned —</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Budget Min (৳)</label>
                                <input wire:model="fBudgetMin" type="number" placeholder="0"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Budget Max (৳)</label>
                                <input wire:model="fBudgetMax" type="number" placeholder="0"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Status</label>
                                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:6px;">
                                    @foreach(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','site_visit'=>'Site Visit','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost'] as $val => $lbl)
                                    <label style="cursor:pointer;">
                                        <input wire:model="fStatus" type="radio" value="{{ $val }}" style="display:none;">
                                        <span :style="fStatus === '{{ $val }}' ? 'background:var(--accent); color:white; border-color:var(--accent);' : 'background:var(--canvas); color:var(--ink-2); border-color:var(--rule);'"
                                              style="display:block; text-align:center; padding:5px 4px; border:1px solid; border-radius:6px; font:500 10px 'Inter',sans-serif; letter-spacing:.03em; transition:.15s;">
                                            {{ $lbl }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Address</label>
                                <textarea wire:model="fAddress" rows="2" placeholder="Full address..."
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                            </div>
                        </div>
                        <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                            <button type="button" @click="$wire.set('formStep', 2)"
                                style="background:var(--accent); color:white; border:none; padding:9px 20px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                                Next →
                            </button>
                        </div>
                    </div>

                    {{-- STEP 2: Social & Extra --}}
                    <div x-show="formStep == 2">
                        <p style="font:600 11px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.06em; text-transform:uppercase; margin-bottom:12px;">Social Profiles</p>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                            @foreach(['fFacebook'=>'Facebook','fWhatsapp'=>'WhatsApp','fInstagram'=>'Instagram','fLinkedin'=>'LinkedIn'] as $field => $label)
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">{{ $label }}</label>
                                <input wire:model="{{ $field }}" type="text" placeholder="{{ $label }} URL or handle"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            @endforeach
                        </div>

                        <p style="font:600 11px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.06em; text-transform:uppercase; margin-bottom:12px; padding-top:12px; border-top:1px solid var(--rule);">Extra Data</p>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Occupation</label>
                                <input wire:model="fOccupation" type="text" placeholder="e.g. Businessman"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Company</label>
                                <input wire:model="fCompany" type="text" placeholder="Company name"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Income Range</label>
                                <select wire:model="fIncomeRange"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                                    <option value="">— Select —</option>
                                    @foreach(['<50k'=>'Below 50k','50k-100k'=>'50k–100k','100k-200k'=>'100k–200k','200k-500k'=>'200k–500k','>500k'=>'Above 500k'] as $val=>$lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Family Size</label>
                                <input wire:model="fFamilySize" type="number" min="1" placeholder="e.g. 4"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Preferred Location</label>
                                <input wire:model="fPreferredLocation" type="text" placeholder="e.g. Uttara"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Unit Type</label>
                                <input wire:model="fUnitType" type="text" placeholder="e.g. 3BHK"
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Remarks</label>
                                <textarea wire:model="fRemarks" rows="2" placeholder="Any special requirements..."
                                    style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                            </div>
                        </div>
                        <div style="margin-top:20px; display:flex; justify-content:space-between;">
                            <button type="button" @click="$wire.set('formStep', 1)"
                                style="background:var(--canvas); color:var(--ink-2); border:1px solid var(--rule); padding:9px 20px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                                ← Back
                            </button>
                            <button type="button" @click="$wire.set('formStep', 3)"
                                style="background:var(--accent); color:white; border:none; padding:9px 20px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                                Next →
                            </button>
                        </div>
                    </div>

                    {{-- STEP 3: Notes & Files --}}
                    <div x-show="formStep == 3">
                        <div style="margin-bottom:16px;">
                            <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Notes</label>
                            <textarea wire:model="fNotes" rows="4" placeholder="Internal notes about this lead..."
                                style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Closed Reason (if lost)</label>
                            <input wire:model="fClosedReason" type="text" placeholder="Reason for losing this lead..."
                                style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                        </div>
                        <div style="margin-bottom:20px;">
                            <x-media-picker-field
                                field="attachments"
                                label="Attachments"
                                placeholder="Select attachments"
                                :value="$attachments"
                                :multiple="true"
                                type="any"
                            />
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <button type="button" @click="$wire.set('formStep', 2)"
                                style="background:var(--canvas); color:var(--ink-2); border:1px solid var(--rule); padding:9px 20px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                                ← Back
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="background:var(--ink-1); color:white; border:none; padding:9px 24px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                                <span wire:loading.remove wire:target="saveLead">Save Lead</span>
                                <span wire:loading wire:target="saveLead">Saving…</span>
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>
