<div
    x-data="{
        activeTab: $wire.entangle('activeTab'),
        followupModal: $wire.entangle('followupModal'),
        taskModal: $wire.entangle('taskModal'),
        convertModal: $wire.entangle('convertModal'),
    }"
    x-init="$store.pageName = { name: 'Lead Detail', slug: 'leads' }"
    style="
        --paper:#FCFBF7; --canvas:#F2EFE7; --ink-1:#1A1814; --ink-2:#5C5648;
        --ink-3:#9B9686; --rule:#EAE5D9; --accent:#1F3A68; --mono:'IBM Plex Mono',ui-monospace,monospace;
        font-family:'Inter',system-ui,sans-serif; color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen">

    {{-- ─── HEADER ─────────────────────────────────────────────────────────── --}}
    <div style="padding:24px 24px 0;">
        <div style="font-size:11px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:8px;">
            <a href="{{ route('admin.crm.leads.index') }}" style="color:var(--ink-3); text-decoration:none; hover:text-decoration:underline;">CRM</a>
            <span style="opacity:.4">/</span>
            <a href="{{ route('admin.crm.leads.index') }}" style="color:var(--ink-3); text-decoration:none;">Leads</a>
            <span style="opacity:.4">/</span>
            <span style="color:var(--ink-1);">{{ $lead->lead_no }}</span>
        </div>

        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h1 style="font-size:22px; font-weight:700; margin:0;">{{ $lead->name }}</h1>
                    @php
                        $statusStyles = [
                            'new'=>'background:#EFF6FF;color:#1E40AF;','contacted'=>'background:#F0FDF4;color:#166534;',
                            'qualified'=>'background:#F5F3FF;color:#5B21B6;','site_visit'=>'background:#FFFBEB;color:#92400E;',
                            'negotiation'=>'background:#FFF1F2;color:#9F1239;','won'=>'background:#D1FAE5;color:#065F46;',
                            'lost'=>'background:#FEE2E2;color:#991B1B;',
                        ];
                        $statusLabels = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','site_visit'=>'Site Visit','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost'];
                    @endphp
                    <span style="padding:3px 10px; border-radius:20px; font:600 10px 'Inter',sans-serif; letter-spacing:.04em;
                                 {{ $statusStyles[$lead->status] ?? '' }}">
                        {{ $statusLabels[$lead->status] ?? $lead->status }}
                    </span>
                    <span style="font-family:var(--mono); font-size:11px; color:var(--ink-3);">{{ $lead->lead_no }}</span>
                </div>
                <div style="margin-top:6px; display:flex; gap:16px; flex-wrap:wrap;">
                    <span style="font-size:13px; color:var(--ink-2);">📞 {{ $lead->phone }}</span>
                    @if($lead->email) <span style="font-size:13px; color:var(--ink-2);">✉ {{ $lead->email }}</span> @endif
                    @if($lead->source) <span style="font-size:13px; color:var(--ink-2);">⚫ {{ $lead->source->name }}</span> @endif
                    @if($lead->assignedUser) <span style="font-size:13px; color:var(--ink-2);">👤 {{ $lead->assignedUser->name }}</span> @endif
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @if(!$lead->converted_customer_id)
                    @can('crm.lead.convert')
                    <button @click="convertModal = true"
                        style="background:#10B981; color:white; border:none; padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        ✅ Convert to Customer
                    </button>
                    @endcan
                @else
                    <a href="{{ route('admin.crm.customers.show', $lead->converted_customer_id) }}"
                       style="background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7; padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; text-decoration:none;">
                        ✅ View Customer
                    </a>
                @endif
                <button @click="$wire.openFollowupCreate()"
                    style="background:var(--accent); color:white; border:none; padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                    + Follow-up
                </button>
                <button @click="$wire.openTaskCreate()"
                    style="background:var(--canvas); color:var(--ink-2); border:1px solid var(--rule); padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                    + Task
                </button>
            </div>
        </div>
    </div>

    <div style="padding:0 24px 80px; display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

        {{-- ─── MAIN COLUMN ────────────────────────────────────────────────── --}}
        <div>

            {{-- ── Tab Panel ─────────────────────────────────────────────────── --}}
            @php
                $fuCount   = $lead->followups->where('status','pending')->count();
                $taskCount = $lead->tasks->where('status','todo')->count();
                $fileCount = $lead->fileables->count();
            @endphp

            {{-- Tab nav --}}
            @php
                $tabs = [
                    'timeline'  => ['label'=>'Timeline',   'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',      'badge'=>0,          'badgeCls'=>''],
                    'followups' => ['label'=>'Follow-ups', 'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'badge'=>$fuCount,   'badgeCls'=>'bg-blue-100 text-blue-700'],
                    'tasks'     => ['label'=>'Tasks',      'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'badge'=>$taskCount, 'badgeCls'=>'bg-amber-100 text-amber-700'],
                    'files'     => ['label'=>'Files',      'icon'=>'M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z', 'badge'=>$fileCount, 'badgeCls'=>'bg-gray-100 text-gray-500'],
                ];
            @endphp
            <div class="flex items-end gap-1 px-2 pt-1 rounded-t-xl border border-b-0 border-[var(--rule)] bg-[var(--paper)]">
                @foreach($tabs as $tab => $meta)
                <button
                    type="button"
                    @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}'
                        ? 'border-[var(--rule)] border-b-[var(--paper)] bg-[var(--paper)] text-[var(--accent)] font-semibold'
                        : 'border-transparent bg-[var(--canvas)] text-[var(--ink-3)] hover:text-[var(--ink-2)] hover:bg-[var(--paper)]'"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-[13px] font-medium
                           border border-b-0 rounded-t-lg cursor-pointer whitespace-nowrap
                           transition-all duration-150 outline-none focus:outline-none relative top-px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                         stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-70">
                        <path d="{{ $meta['icon'] }}"/>
                    </svg>
                    <span>{{ $meta['label'] }}</span>
                    @if($meta['badge'] > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold leading-none {{ $meta['badgeCls'] }}">
                        {{ $meta['badge'] }}
                    </span>
                    @endif
                </button>
                @endforeach
            </div>

            {{-- Tab body wrapper --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-top:none; border-radius:0 0 12px 12px; padding:20px;">

            {{-- Timeline Tab --}}
            <div x-show="activeTab === 'timeline'">
                {{-- Log activity --}}
                <div style="background:var(--canvas); border:1px solid var(--rule); border-radius:10px; padding:16px; margin-bottom:20px;">
                    <p style="font:600 10px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.07em; text-transform:uppercase; margin:0 0 10px;">Log Activity</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach([
                            'note'       => ['label'=>'Note',      'icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                            'call'       => ['label'=>'Call',      'icon'=>'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                            'email'      => ['label'=>'Email',     'icon'=>'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            'whatsapp'   => ['label'=>'WhatsApp',  'icon'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                            'site_visit' => ['label'=>'Site Visit','icon'=>'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                            'meeting'    => ['label'=>'Meeting',   'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                        ] as $val => $meta)
                        <label class="cursor-pointer">
                            <input wire:model="activityType" type="radio" value="{{ $val }}" class="sr-only peer">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-all duration-150 cursor-pointer select-none
                                         border-gray-200 bg-white text-gray-500
                                         peer-checked:bg-(--accent) peer-checked:text-white peer-checked:border-(--accent)
                                         hover:border-gray-300 hover:text-gray-700">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                                    <path d="{{ $meta['icon'] }}"/>
                                </svg>
                                {{ $meta['label'] }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    <textarea wire:model="activityDesc" rows="3" placeholder="What happened? Notes, call outcome, email summary..."
                        style="width:100%; padding:9px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--paper); color:var(--ink-1); outline:none; resize:vertical; box-sizing:border-box; margin-bottom:10px;"></textarea>
                    @error('activityDesc') <p style="color:#EF4444; font-size:11px; margin-bottom:8px;">{{ $message }}</p> @enderror
                    <button wire:click="logActivity" wire:loading.attr="disabled"
                        style="background:var(--ink-1); color:white; border:none; padding:7px 18px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">
                        Log Activity
                    </button>
                </div>

                {{-- Activity list --}}
                <div style="position:relative; padding-left:26px;">
                    <div style="position:absolute; left:8px; top:6px; bottom:6px; width:1px; background:var(--rule);"></div>
                    @forelse($lead->activities as $act)
                    <div style="position:relative; margin-bottom:12px;">
                        <div style="position:absolute; left:-20px; top:14px; width:10px; height:10px; border-radius:50%; background:var(--accent); border:2px solid var(--paper); box-shadow:0 0 0 1px var(--accent);"></div>
                        <div style="background:var(--canvas); border:1px solid var(--rule); border-radius:8px; padding:11px 14px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <span style="font:600 11px 'Inter',sans-serif; color:var(--accent); text-transform:capitalize; letter-spacing:.03em;">{{ str_replace('_',' ',$act->type) }}</span>
                                <span style="font:11px var(--mono); color:var(--ink-3);">{{ $act->created_at->diffForHumans() }}</span>
                            </div>
                            <p style="font-size:13px; color:var(--ink-1); margin:0; line-height:1.5;">{{ $act->description }}</p>
                            @if($act->createdByUser)
                            <div style="margin-top:5px; font:11px var(--mono); color:var(--ink-3);">by {{ $act->createdByUser->name }}</div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div style="background:var(--canvas); border:1px dashed var(--rule); border-radius:8px; padding:28px; text-align:center; color:var(--ink-3); font-size:13px;">
                        No activity yet. Log the first interaction above.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Follow-ups Tab --}}
            <div x-show="activeTab === 'followups'">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <p style="font:600 10px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.07em; text-transform:uppercase; margin:0;">Scheduled Follow-ups</p>
                    <button @click="$wire.openFollowupCreate()"
                        style="background:var(--accent); color:white; border:none; padding:6px 14px; border-radius:7px; font:600 11px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Schedule
                    </button>
                </div>
                @forelse($lead->followups->sortBy('scheduled_at') as $fu)
                <div style="background:var(--canvas); border:1px solid {{ $fu->isOverdue() ? '#FCA5A5' : 'var(--rule)' }}; border-left:3px solid {{ $fu->isOverdue() ? '#EF4444' : 'var(--accent)' }}; border-radius:8px; padding:14px 16px; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                        <div style="flex:1;">
                            <div style="display:flex; align-items:center; gap:7px; margin-bottom:6px; flex-wrap:wrap;">
                                <span style="font:600 12px 'Inter',sans-serif; color:var(--ink-1); text-transform:capitalize;">{{ str_replace('_',' ',$fu->type) }}</span>
                                @php $fuStatusStyle = ['pending'=>'background:#EFF6FF;color:#1E40AF;','done'=>'background:#D1FAE5;color:#065F46;','cancelled'=>'background:#F3F4F6;color:#374151;','rescheduled'=>'background:#FEF3C7;color:#92400E;'][$fu->status] ?? ''; @endphp
                                <span style="padding:2px 8px; border-radius:20px; font:700 9px 'Inter',sans-serif; {{ $fuStatusStyle }}">{{ ucfirst($fu->status) }}</span>
                                @if($fu->isOverdue()) <span style="padding:2px 8px; border-radius:20px; font:700 9px 'Inter',sans-serif; background:#FEE2E2; color:#991B1B;">OVERDUE</span> @endif
                            </div>
                            <div style="font:12px var(--mono); color:var(--ink-2); margin-bottom:4px;">{{ $fu->scheduled_at->format('d M Y, H:i') }}</div>
                            @if($fu->assignedUser) <div style="font-size:12px; color:var(--ink-3); margin-bottom:2px;">{{ $fu->assignedUser->name }}</div> @endif
                            @if($fu->notes) <div style="font-size:12px; color:var(--ink-2); margin-top:5px; line-height:1.4;">{{ $fu->notes }}</div> @endif
                            @if($fu->outcome) <div style="font-size:11px; color:var(--ink-3); margin-top:4px; font-style:italic;">Outcome: {{ $fu->outcome }}</div> @endif
                        </div>
                        <div style="display:flex; gap:5px; flex-shrink:0;">
                            <button @click="$wire.openFollowupEdit({{ $fu->id }})"
                                style="padding:4px 10px; border:1px solid var(--rule); border-radius:6px; font:11px 'Inter',sans-serif; color:var(--ink-2); background:var(--paper); cursor:pointer;">Edit</button>
                            <button x-data="livewireConfirm"
                                @click="confirmAction({ id:{{ $fu->id }}, method:'deleteFollowup', title:'Delete follow-up?', text:'This cannot be undone.' })"
                                style="padding:4px 10px; border:1px solid #FCA5A5; border-radius:6px; font:11px 'Inter',sans-serif; color:#991B1B; background:#FEF2F2; cursor:pointer;">Del</button>
                        </div>
                    </div>
                </div>
                @empty
                <div style="background:var(--canvas); border:1px dashed var(--rule); border-radius:8px; padding:32px; text-align:center; color:var(--ink-3); font-size:13px;">
                    No follow-ups scheduled yet.
                </div>
                @endforelse
            </div>

            {{-- Tasks Tab --}}
            <div x-show="activeTab === 'tasks'">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <p style="font:600 10px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.07em; text-transform:uppercase; margin:0;">Task List</p>
                    <button @click="$wire.openTaskCreate()"
                        style="background:var(--accent); color:white; border:none; padding:6px 14px; border-radius:7px; font:600 11px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Task
                    </button>
                </div>
                @forelse($lead->tasks->sortBy('due_at') as $task)
                @php $prioStyles = ['urgent'=>'background:#FEE2E2;color:#991B1B;','high'=>'background:#FEF3C7;color:#92400E;','medium'=>'background:#EFF6FF;color:#1E40AF;','low'=>'background:#F3F4F6;color:#374151;']; @endphp
                <div style="background:var(--canvas); border:1px solid {{ $task->isOverdue() ? '#FCA5A5' : 'var(--rule)' }}; border-radius:8px; padding:12px 14px; margin-bottom:8px; display:flex; align-items:flex-start; gap:12px;">
                    {{-- Checkbox --}}
                    @if($task->status !== 'done')
                    <button wire:click="markDoneTask({{ $task->id }})"
                        style="margin-top:2px; width:18px; height:18px; border:2px solid var(--rule); border-radius:4px; background:var(--paper); cursor:pointer; flex-shrink:0;"
                        title="Mark done"></button>
                    @else
                    <div style="margin-top:2px; width:18px; height:18px; border:2px solid #10B981; border-radius:4px; background:#10B981; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="m20 6-11 11-5-5"/></svg>
                    </div>
                    @endif
                    {{-- Content --}}
                    <div style="flex:1; min-width:0;">
                        <p style="font:600 13px 'Inter',sans-serif; margin:0 0 5px; {{ $task->status === 'done' ? 'text-decoration:line-through;color:var(--ink-3);' : 'color:var(--ink-1);' }}">{{ $task->title }}</p>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <span style="padding:2px 7px; border-radius:20px; font:700 9px 'Inter',sans-serif; {{ $prioStyles[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                            @if($task->due_at)
                            <span style="font:11px var(--mono); color:{{ $task->isOverdue() ? '#991B1B' : 'var(--ink-3)' }};">Due {{ $task->due_at->format('d M Y') }}</span>
                            @endif
                            @if($task->assignedUser) <span style="font-size:11px; color:var(--ink-3);">{{ $task->assignedUser->name }}</span> @endif
                        </div>
                        @if($task->description) <p style="font-size:12px; color:var(--ink-3); margin:5px 0 0; line-height:1.4;">{{ $task->description }}</p> @endif
                    </div>
                    {{-- Actions --}}
                    <div style="display:flex; gap:5px; flex-shrink:0; margin-top:1px;">
                        <button @click="$wire.openTaskEdit({{ $task->id }})"
                            style="padding:4px 10px; border:1px solid var(--rule); border-radius:6px; font:11px 'Inter',sans-serif; color:var(--ink-2); background:var(--paper); cursor:pointer;">Edit</button>
                        <button x-data="livewireConfirm"
                            @click="confirmAction({ id:{{ $task->id }}, method:'deleteTask', title:'Delete task?', text:'This cannot be undone.' })"
                            style="padding:4px 10px; border:1px solid #FCA5A5; border-radius:6px; font:11px 'Inter',sans-serif; color:#991B1B; background:#FEF2F2; cursor:pointer;">Del</button>
                    </div>
                </div>
                @empty
                <div style="background:var(--canvas); border:1px dashed var(--rule); border-radius:8px; padding:32px; text-align:center; color:var(--ink-3); font-size:13px;">
                    No tasks yet.
                </div>
                @endforelse
            </div>

            {{-- Files Tab --}}
            <div x-show="activeTab === 'files'">
                <div style="background:var(--canvas); border:1px solid var(--rule); border-radius:10px; padding:16px; margin-bottom:16px;">
                    <p style="font:600 10px 'Inter',sans-serif; color:var(--ink-3); letter-spacing:.07em; text-transform:uppercase; margin:0 0 10px;">Attach Files</p>
                    <x-media-picker-field
                        field="leadFiles"
                        label="Select Files"
                        placeholder="Select files from media library"
                        :value="$leadFiles"
                        :multiple="true"
                        type="any"
                    />
                    <button wire:click="attachSelectedFiles" wire:loading.attr="disabled"
                        style="margin-top:12px; background:var(--ink-1); color:white; border:none; padding:8px 18px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <span wire:loading.remove wire:target="attachSelectedFiles">Attach Selected</span>
                        <span wire:loading wire:target="attachSelectedFiles">Attaching…</span>
                    </button>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px;">
                    @forelse($lead->fileables as $fileable)
                    @php $file = $fileable->file; @endphp
                    @if($file)
                    <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                        @php
                            $imgExts = ['jpg','jpeg','png','gif','webp','svg'];
                            $isImg   = in_array(strtolower($file->extension ?? ''), $imgExts);
                            $fileUrl = file_path($file->id);
                        @endphp
                        @if($isImg)
                        <div style="height:120px; background:var(--canvas); overflow:hidden;">
                            <img src="{{ $fileUrl }}" alt="{{ $file->name }}"
                                 style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        @else
                        <div style="height:80px; background:var(--canvas); display:flex; align-items:center; justify-content:center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--ink-3);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        @endif
                        <div style="padding:10px 12px;">
                            <div style="font:600 12px 'Inter',sans-serif; color:var(--ink-1); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:4px;" title="{{ $file->name }}">
                                {{ $file->name }}
                            </div>
                            <div style="font:11px var(--mono); color:var(--ink-3); margin-bottom:8px; text-transform:uppercase;">
                                {{ $file->extension ?? 'file' }} · {{ $fileable->category }}
                            </div>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ $fileUrl }}" target="_blank"
                                   style="flex:1; text-align:center; padding:4px 8px; border:1px solid var(--rule); border-radius:5px; font:12px 'Inter',sans-serif; color:var(--accent); text-decoration:none; background:var(--canvas);">
                                    View
                                </a>
                                <button x-data="livewireConfirm"
                                    @click="confirmAction({ id:{{ $fileable->id }}, method:'detachFile', title:'Remove file?', text:'This removes the attachment from this lead.' })"
                                    style="padding:4px 8px; border:1px solid #FCA5A5; border-radius:5px; font:12px 'Inter',sans-serif; color:#991B1B; background:#FEF2F2; cursor:pointer;">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                    @empty
                    <div style="grid-column:1/-1; background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:32px; text-align:center; color:var(--ink-3); font-size:14px;">
                        No files attached yet. Use the picker above to attach files from the media library.
                    </div>
                    @endforelse
                </div>
            </div>

            </div>{{-- end tab body wrapper --}}

        </div>

        {{-- ─── SIDEBAR ─────────────────────────────────────────────────────── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Lead Score --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px;">
                <p style="font:600 10px 'Inter',sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin:0 0 12px;">CRM Score</p>
                <div style="font:700 32px var(--mono); color:{{ $lead->score >= 70 ? '#10B981' : ($lead->score >= 40 ? '#F59E0B' : '#EF4444') }}; margin-bottom:6px;">{{ $lead->score }}<span style="font-size:14px; color:var(--ink-3);">/100</span></div>
                <div style="background:var(--canvas); border-radius:4px; height:6px; overflow:hidden;">
                    <div style="height:6px; border-radius:4px; width:{{ $lead->score }}%; background:{{ $lead->score >= 70 ? '#10B981' : ($lead->score >= 40 ? '#F59E0B' : '#EF4444') }};"></div>
                </div>
                <div style="margin-top:10px; font-size:11px; color:var(--ink-3); display:flex; flex-direction:column; gap:4px;">
                    @if(!empty($lead->social_profiles['facebook'])) <span style="color:#10B981;">✔ Facebook profile</span> @else <span>✗ Facebook profile</span> @endif
                    @if(!empty($lead->social_profiles['whatsapp'])) <span style="color:#10B981;">✔ WhatsApp</span> @else <span>✗ WhatsApp</span> @endif
                    @if(!empty($lead->extra_data['income_range'])) <span style="color:#10B981;">✔ Income range</span> @else <span>✗ Income range</span> @endif
                    @if(!empty($lead->extra_data['occupation'])) <span style="color:#10B981;">✔ Occupation</span> @else <span>✗ Occupation</span> @endif
                    @if($lead->fileables->count() > 0) <span style="color:#10B981;">✔ Files uploaded</span> @else <span>✗ Files uploaded</span> @endif
                    @if($lead->email) <span style="color:#10B981;">✔ Email available</span> @else <span>✗ Email available</span> @endif
                </div>
            </div>

            {{-- Lead Details --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px;">
                <p style="font:600 10px 'Inter',sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin:0 0 12px;">Lead Details</p>
                @php $rows = [
                    ['label'=>'Lead No','value'=>$lead->lead_no],
                    ['label'=>'Status','value'=>ucfirst($lead->status)],
                    ['label'=>'Source','value'=>$lead->source?->name ?? '—'],
                    ['label'=>'Project','value'=>$lead->project?->name ?? '—'],
                    ['label'=>'Assigned To','value'=>$lead->assignedUser?->name ?? 'Unassigned'],
                    ['label'=>'Budget','value'=>($lead->budget_min||$lead->budget_max) ? '৳'.number_format($lead->budget_min??0).' – '.number_format($lead->budget_max??0) : '—'],
                    ['label'=>'Created','value'=>$lead->created_at->format('d M Y')],
                ]; @endphp
                @foreach($rows as $row)
                <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--rule); {{ $loop->last ? 'border-bottom:none;' : '' }}">
                    <span style="font-size:12px; color:var(--ink-3);">{{ $row['label'] }}</span>
                    <span style="font:600 12px 'Inter',sans-serif; color:var(--ink-1);">{{ $row['value'] }}</span>
                </div>
                @endforeach
            </div>

            {{-- Social Profiles --}}
            @if($lead->social_profiles)
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px;">
                <p style="font:600 10px 'Inter',sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin:0 0 12px;">Social Profiles</p>
                @foreach(['facebook'=>'Facebook','whatsapp'=>'WhatsApp','instagram'=>'Instagram','linkedin'=>'LinkedIn'] as $key=>$label)
                    @if(!empty($lead->social_profiles[$key]))
                    <div style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid var(--rule);">
                        <span style="font-size:12px; color:var(--ink-3);">{{ $label }}</span>
                        <a href="{{ $lead->social_profiles[$key] }}" target="_blank" style="font-size:12px; color:var(--accent); text-decoration:none; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Link ↗</a>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Extra Data --}}
            @if($lead->extra_data)
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px;">
                <p style="font:600 10px 'Inter',sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin:0 0 12px;">Profile Data</p>
                @php $extraFields = [
                    'occupation'=>'Occupation','company'=>'Company','income_range'=>'Income Range',
                    'family_size'=>'Family Size','preferred_location'=>'Pref. Location',
                    'unit_type'=>'Unit Type','remarks'=>'Remarks',
                ]; @endphp
                @foreach($extraFields as $key=>$label)
                    @if(!empty($lead->extra_data[$key]))
                    <div style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid var(--rule);">
                        <span style="font-size:12px; color:var(--ink-3);">{{ $label }}</span>
                        <span style="font:600 12px 'Inter',sans-serif; color:var(--ink-1); max-width:160px; text-align:right;">{{ $lead->extra_data[$key] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Conversion info --}}
            @if($lead->converted_customer_id)
            <div style="background:#D1FAE5; border:1px solid #6EE7B7; border-radius:10px; padding:18px;">
                <p style="font:600 10px 'Inter',sans-serif; letter-spacing:.07em; text-transform:uppercase; color:#065F46; margin:0 0 8px;">Converted</p>
                <p style="font-size:13px; color:#065F46; margin:0;">Converted to customer on {{ $lead->converted_at?->format('d M Y') }}</p>
                <a href="{{ route('admin.crm.customers.show', $lead->converted_customer_id) }}"
                   style="display:inline-block; margin-top:10px; padding:6px 14px; background:#10B981; color:white; border-radius:6px; font:600 11px 'Inter',sans-serif; text-decoration:none;">
                    View Customer →
                </a>
            </div>
            @endif

        </div>
    </div>

    {{-- ─── FOLLOW-UP MODAL ──────────────────────────────────────────────────── --}}
    <div x-show="followupModal" x-cloak style="position:fixed; inset:0; z-index:60; display:flex; align-items:center; justify-content:center; padding:24px;">
        <div @click="$wire.set('followupModal', false)" style="position:absolute; inset:0; background:rgba(0,0,0,.45);"></div>
        <div style="position:relative; width:460px; max-height:90vh; overflow-y:auto; background:var(--paper); border-radius:14px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <h3 style="font-size:16px; font-weight:600; margin:0 0 18px;">Schedule Follow-up</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Type</label>
                    <select wire:model="fuType" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        @foreach(['call'=>'Call','email'=>'Email','whatsapp'=>'WhatsApp','meeting'=>'Meeting','site_visit'=>'Site Visit','other'=>'Other'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Scheduled At <span style="color:#EF4444">*</span></label>
                    <input wire:model="fuScheduledAt" type="datetime-local"
                        style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Assigned To</label>
                    <select wire:model="fuAssignedTo" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        <option value="">— Select —</option>
                        @foreach($users as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Status</label>
                    <select wire:model="fuStatus" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        @foreach(['pending'=>'Pending','done'=>'Done','cancelled'=>'Cancelled','rescheduled'=>'Rescheduled'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Notes</label>
                    <textarea wire:model="fuNotes" rows="2" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Outcome</label>
                    <textarea wire:model="fuOutcome" rows="2" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                </div>
            </div>
            <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:8px;">
                <button @click="$wire.set('followupModal', false)"
                    style="padding:8px 18px; border:1px solid var(--rule); border-radius:7px; font:600 12px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-2); cursor:pointer;">Cancel</button>
                <button wire:click="saveFollowup"
                    style="padding:8px 18px; background:var(--accent); color:white; border:none; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">Save</button>
            </div>
        </div>
    </div>

    {{-- ─── TASK MODAL ──────────────────────────────────────────────────────── --}}
    <div x-show="taskModal" x-cloak style="position:fixed; inset:0; z-index:60; display:flex; align-items:center; justify-content:center; padding:24px;">
        <div @click="$wire.set('taskModal', false)" style="position:absolute; inset:0; background:rgba(0,0,0,.45);"></div>
        <div style="position:relative; width:460px; max-height:90vh; overflow-y:auto; background:var(--paper); border-radius:14px; padding:24px; box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <h3 style="font-size:16px; font-weight:600; margin:0 0 18px;">Task</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div style="grid-column:1/-1;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Title <span style="color:#EF4444">*</span></label>
                    <input wire:model="tTitle" type="text" placeholder="Task title"
                        style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                    @error('tTitle') <p style="color:#EF4444; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Type</label>
                    <select wire:model="tType" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        @foreach(['call'=>'Call','email'=>'Email','meeting'=>'Meeting','follow_up'=>'Follow Up','document'=>'Document','other'=>'Other'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Priority</label>
                    <select wire:model="tPriority" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Status</label>
                    <select wire:model="tStatus" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        @foreach(['todo'=>'To Do','in_progress'=>'In Progress','done'=>'Done','cancelled'=>'Cancelled'] as $v=>$l)
                        <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Due At</label>
                    <input wire:model="tDueAt" type="datetime-local"
                        style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Assigned To</label>
                    <select wire:model="tAssignedTo" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                        <option value="">— Select —</option>
                        @foreach($users as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Description</label>
                    <textarea wire:model="tDescription" rows="2" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                </div>
            </div>
            <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:8px;">
                <button @click="$wire.set('taskModal', false)"
                    style="padding:8px 18px; border:1px solid var(--rule); border-radius:7px; font:600 12px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-2); cursor:pointer;">Cancel</button>
                <button wire:click="saveTask"
                    style="padding:8px 18px; background:var(--accent); color:white; border:none; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">Save Task</button>
            </div>
        </div>
    </div>

    {{-- ─── CONVERT MODAL ──────────────────────────────────────────────────── --}}
    <div x-show="convertModal" x-cloak style="position:fixed; inset:0; z-index:60; display:flex; align-items:center; justify-content:center; padding:24px;">
        <div @click="convertModal = false" style="position:absolute; inset:0; background:rgba(0,0,0,.45);"></div>
        <div style="position:relative; width:400px; background:var(--paper); border-radius:14px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,.2); text-align:center;">
            <div style="font-size:48px; margin-bottom:12px;">✅</div>
            <h3 style="font-size:18px; font-weight:700; margin:0 0 8px;">Convert to Customer?</h3>
            <p style="font-size:13px; color:var(--ink-2); margin:0 0 20px; line-height:1.5;">
                This will create a new customer record for <strong>{{ $lead->name }}</strong> and mark this lead as <strong>Won</strong>.
            </p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button @click="convertModal = false"
                    style="padding:9px 20px; border:1px solid var(--rule); border-radius:7px; font:600 13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-2); cursor:pointer;">Cancel</button>
                <button wire:click="convertToCustomer" wire:loading.attr="disabled"
                    style="padding:9px 20px; background:#10B981; color:white; border:none; border-radius:7px; font:600 13px 'Inter',sans-serif; cursor:pointer;">
                    <span wire:loading.remove wire:target="convertToCustomer">Yes, Convert</span>
                    <span wire:loading wire:target="convertToCustomer">Converting…</span>
                </button>
            </div>
        </div>
    </div>

</div>
