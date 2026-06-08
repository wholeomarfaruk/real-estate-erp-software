<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'Campaigns', slug: 'marketing' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--mono:'IBM Plex Mono',monospace;font-family:'Inter',system-ui,sans-serif;color:var(--ink-1);background:var(--canvas);"
    class="min-h-screen">

    {{-- Header --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <div style="font-size:11px;color:var(--ink-3);font-family:var(--mono);display:flex;gap:6px;align-items:center;margin-bottom:6px;">
                <span>Marketing</span><span style="opacity:.4">/</span><span style="color:var(--ink-1)">Campaigns</span>
            </div>
            <h1 style="font-size:24px;font-weight:600;letter-spacing:-.01em;margin:0;">Campaigns</h1>
            <p style="margin-top:4px;font-size:13px;color:var(--ink-2);">Create and launch SMS/Email campaigns to your audiences.</p>
        </div>
        @can('marketing.campaign.create')
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white cursor-pointer border-0"
            style="background:var(--ink-1);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Campaign
        </button>
        @endcan
    </div>

    <div style="padding:20px 24px 80px;">
        {{-- KPI --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--rule);border:1px solid var(--rule);border-radius:10px;overflow:hidden;margin-bottom:20px;">
            @foreach([['label'=>'Total','value'=>$kpi['total'],'fg'=>'var(--ink-1)'],['label'=>'Running','value'=>$kpi['running'],'fg'=>'#065F46'],['label'=>'Completed','value'=>$kpi['completed'],'fg'=>'#1D4ED8'],['label'=>'Draft','value'=>$kpi['draft'],'fg'=>'var(--ink-3)']] as $s)
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search campaigns…"
                    style="width:100%;padding:7px 10px 7px 32px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;box-sizing:border-box;">
            </div>
            <select wire:model.live="filterStatus" style="padding:7px 10px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                <option value="all">All Status</option>
                <option value="draft">Draft</option>
                <option value="queued">Queued</option>
                <option value="running">Running</option>
                <option value="completed">Completed</option>
                <option value="paused">Paused</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        {{-- Table --}}
        <div style="background:var(--paper);border:1px solid var(--rule);border-radius:10px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas);border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Name</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Type</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Audience</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Stats</th>
                        <th style="padding:10px 16px;text-align:left;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px;text-align:right;font:600 10px 'Inter',sans-serif;letter-spacing:.06em;text-transform:uppercase;color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                    @php
                        $statusColors = [
                            'draft'     => 'background:#F3F4F6;color:#374151;',
                            'queued'    => 'background:#FEF3C7;color:#92400E;',
                            'running'   => 'background:#D1FAE5;color:#065F46;',
                            'completed' => 'background:#DBEAFE;color:#1D4ED8;',
                            'paused'    => 'background:#FDE8D8;color:#9A3412;',
                            'failed'    => 'background:#FEE2E2;color:#991B1B;',
                        ];
                        $typeColors = ['sms'=>'background:#DBEAFE;color:#1D4ED8;','email'=>'background:#EDE9FE;color:#6D28D9;','both'=>'background:#D1FAE5;color:#065F46;'];
                        $stats = $camp->stats ?? [];
                    @endphp
                    <tr style="border-bottom:1px solid var(--rule);">
                        <td style="padding:12px 16px;">
                            <div style="font-weight:600;color:var(--ink-1);">{{ $camp->name }}</div>
                            @if($camp->description)
                            <div style="font-size:11px;color:var(--ink-3);margin-top:2px;">{{ Str::limit($camp->description,50) }}</div>
                            @endif
                            <div style="font:10px var(--mono);color:var(--ink-3);margin-top:3px;">{{ $camp->created_at->format('d M Y') }}</div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $typeColors[$camp->type] ?? '' }}">{{ strtoupper($camp->type) }}</span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div style="font-size:12px;color:var(--ink-2);">{{ $camp->audience?->name ?? '—' }}</div>
                            @if($camp->template)
                            <div style="font-size:11px;color:var(--ink-3);margin-top:1px;">{{ $camp->template->name }}</div>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            @if(!empty($stats))
                            <div style="font:11px var(--mono);color:var(--ink-2);">
                                <span style="color:#065F46;">{{ $stats['sent'] ?? 0 }} sent</span>
                                @if(($stats['failed'] ?? 0) > 0)
                                <span style="color:#991B1B;margin-left:6px;">{{ $stats['failed'] }} failed</span>
                                @endif
                            </div>
                            @else
                            <span style="color:var(--ink-3);font-size:11px;">—</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px;border-radius:20px;font:600 10px 'Inter',sans-serif;{{ $statusColors[$camp->status] ?? '' }}">{{ ucfirst($camp->status) }}</span>
                        </td>
                        <td style="padding:12px 16px;text-align:right;">
                            <div class="flex justify-end gap-2">
                                @can('marketing.campaign.send')
                                @if(in_array($camp->status, ['draft', 'paused']))
                                <button wire:click="launch({{ $camp->id }})" wire:loading.attr="disabled" wire:confirm="Launch this campaign now? This will send messages to all audience members."
                                    style="padding:5px 10px;border:1px solid #34D399;border-radius:6px;font:600 12px 'Inter',sans-serif;color:#065F46;background:#D1FAE5;cursor:pointer;">
                                    Launch
                                </button>
                                @endif
                                @endcan
                                @can('marketing.campaign.edit')
                                @if($camp->status === 'draft')
                                <button wire:click="openEdit({{ $camp->id }})" style="padding:5px 10px;border:1px solid var(--rule);border-radius:6px;font:12px 'Inter',sans-serif;color:var(--ink-2);background:var(--canvas);cursor:pointer;">Edit</button>
                                @endif
                                @endcan
                                @can('marketing.campaign.delete')
                                @if($camp->status !== 'running')
                                <button x-data="livewireConfirm" @click="confirmAction({id:{{ $camp->id }},method:'delete',title:'Delete campaign?',text:'This cannot be undone.'})" style="padding:5px 10px;border:1px solid #FCA5A5;border-radius:6px;font:12px 'Inter',sans-serif;color:#991B1B;background:#FEF2F2;cursor:pointer;">Del</button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="padding:48px;text-align:center;color:var(--ink-3);">No campaigns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($campaigns->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--rule);background:var(--canvas);">{{ $campaigns->links() }}</div>
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
                <div style="font-weight:600;font-size:16px;" x-text="$wire.editingId ? 'Edit Campaign' : 'New Campaign'"></div>
                <button @click="$wire.closeDrawer()" style="background:none;border:none;cursor:pointer;color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:24px;">
                <form wire:submit="save">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Campaign Name <span class="text-red-500">*</span></label>
                            <input wire:model="fName" type="text" placeholder="e.g. Summer Offer 2026"
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
                                @foreach(['sms'=>'SMS','email'=>'Email','both'=>'Both'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model="fType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Audience <span class="text-red-500">*</span></label>
                            <select wire:model="fAudienceId" style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-1);outline:none;">
                                <option value="">Select audience…</option>
                                @foreach($audiences as $aud)
                                <option value="{{ $aud->id }}">{{ $aud->name }} ({{ number_format($aud->member_count) }})</option>
                                @endforeach
                            </select>
                            @error('fAudienceId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Schedule</label>
                            <div class="flex gap-2 mb-2">
                                @foreach(['now'=>'Send Immediately','scheduled'=>'Schedule for Later'] as $val=>$lbl)
                                <label class="cursor-pointer flex-1">
                                    <input wire:model.live="fScheduleType" type="radio" value="{{ $val }}" class="sr-only peer">
                                    <span class="block text-center py-2 px-3 rounded-lg border text-xs font-semibold transition-all border-gray-200 bg-white text-gray-500 peer-checked:bg-[var(--accent)] peer-checked:text-white peer-checked:border-[var(--accent)]">{{ $lbl }}</span>
                                </label>
                                @endforeach
                            </div>
                            <div x-show="$wire.fScheduleType === 'scheduled'">
                                <input wire:model="fScheduledAt" type="datetime-local"
                                    style="width:100%;padding:8px 12px;border:1px solid var(--rule);border-radius:7px;font:13px 'Inter',sans-serif;background:var(--canvas);outline:none;box-sizing:border-box;">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$wire.closeDrawer()"
                                style="padding:9px 20px;border:1px solid var(--rule);border-radius:7px;font:600 12px 'Inter',sans-serif;background:var(--canvas);color:var(--ink-2);cursor:pointer;">Cancel</button>
                            <button type="submit" wire:loading.attr="disabled"
                                style="padding:9px 24px;background:var(--ink-1);color:white;border:none;border-radius:7px;font:600 12px 'Inter',sans-serif;cursor:pointer;">
                                <span wire:loading.remove wire:target="save">Save Campaign</span>
                                <span wire:loading wire:target="save">Saving…</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
