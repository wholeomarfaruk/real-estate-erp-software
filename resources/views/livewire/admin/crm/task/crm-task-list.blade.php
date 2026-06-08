<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'CRM Tasks', slug: 'crm-tasks' }"
    style="
        --paper:#FCFBF7; --canvas:#F2EFE7; --ink-1:#1A1814; --ink-2:#5C5648;
        --ink-3:#9B9686; --rule:#EAE5D9; --accent:#1F3A68; --mono:'IBM Plex Mono',ui-monospace,monospace;
        font-family:'Inter',system-ui,sans-serif; color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen">

    <div style="padding:28px 24px 0; display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-size:11px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; margin-bottom:6px;">
                <a href="{{ route('admin.crm.leads.index') }}" style="color:var(--ink-3); text-decoration:none;">CRM</a>
                <span style="opacity:.4">/</span>
                <span style="color:var(--ink-1);">Tasks</span>
            </div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">CRM Tasks</h1>
            <p style="margin-top:4px; font-size:13px; color:var(--ink-2);">All tasks across leads and customers.</p>
        </div>
        <button @click="$wire.openCreate()"
            style="background:var(--ink-1); color:var(--paper); border:none; padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Task
        </button>
    </div>

    <div style="padding:20px 24px 80px;">

        {{-- KPI Strip --}}
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1px; background:var(--rule); border:1px solid var(--rule); border-radius:10px; overflow:hidden; margin-bottom:20px;">
            @foreach([
                ['label'=>'To Do','value'=>$kpi['todo'],'fg'=>'#1E40AF','bg'=>'#EFF6FF'],
                ['label'=>'In Progress','value'=>$kpi['in_progress'],'fg'=>'#7C3AED','bg'=>'#F5F3FF'],
                ['label'=>'Done','value'=>$kpi['done'],'fg'=>'#065F46','bg'=>'#D1FAE5'],
                ['label'=>'Overdue','value'=>$kpi['overdue'],'fg'=>'#991B1B','bg'=>'#FEE2E2'],
            ] as $s)
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter',sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">{{ $s['label'] }}</div>
                <div style="margin-top:5px; font:700 22px var(--mono); color:{{ $s['fg'] }}; font-variant-numeric:tabular-nums;">{{ $s['value'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Filters --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:10px 14px; display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:180px;">
                <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ink-3);" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tasks…"
                    style="width:100%; padding:7px 10px 7px 32px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
            </div>
            <select wire:model.live="filterStatus" style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Status</option>
                <option value="todo">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="done">Done</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select wire:model.live="filterPriority" style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Priority</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
            <select wire:model.live="filterAssigned" style="padding:7px 10px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; cursor:pointer;">
                <option value="all">All Assigned</option>
                @foreach($users as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
            </select>
        </div>

        {{-- Task List --}}
        <div style="display:flex; flex-direction:column; gap:10px;">
            @forelse($tasks as $task)
            @php
                $prioStyles = ['urgent'=>'background:#FEE2E2;color:#991B1B;','high'=>'background:#FEF3C7;color:#92400E;','medium'=>'background:#EFF6FF;color:#1E40AF;','low'=>'background:#F3F4F6;color:#374151;'];
                $statusStyles = ['todo'=>'background:#EFF6FF;color:#1E40AF;','in_progress'=>'background:#F5F3FF;color:#5B21B6;','done'=>'background:#D1FAE5;color:#065F46;','cancelled'=>'background:#F3F4F6;color:#374151;'];
                $statusLabels = ['todo'=>'To Do','in_progress'=>'In Progress','done'=>'Done','cancelled'=>'Cancelled'];
            @endphp
            <div style="background:var(--paper); border:1px solid {{ $task->isOverdue() ? '#FCA5A5' : 'var(--rule)' }}; border-radius:10px; padding:16px;
                        {{ $task->isOverdue() ? 'background:#FFF5F5;' : '' }}">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div style="display:flex; gap:12px; flex:1; align-items:flex-start;">
                        @if($task->status !== 'done')
                        <button wire:click="markDone({{ $task->id }})"
                            style="margin-top:2px; width:20px; height:20px; border:2px solid var(--rule); border-radius:5px; background:none; cursor:pointer; flex-shrink:0; transition:.15s;"
                            title="Mark done"></button>
                        @else
                        <div style="margin-top:2px; width:20px; height:20px; border-radius:5px; background:#10B981; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="m20 6-11 11-5-5"/></svg>
                        </div>
                        @endif
                        <div style="flex:1;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:5px;">
                                <span style="font:600 13px 'Inter',sans-serif; {{ $task->status === 'done' ? 'text-decoration:line-through; color:var(--ink-3);' : '' }}">{{ $task->title }}</span>
                                <span style="padding:2px 8px; border-radius:20px; font:600 9px 'Inter',sans-serif; {{ $prioStyles[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                                <span style="padding:2px 8px; border-radius:20px; font:600 9px 'Inter',sans-serif; {{ $statusStyles[$task->status] ?? '' }}">{{ $statusLabels[$task->status] ?? $task->status }}</span>
                                @if($task->isOverdue()) <span style="padding:2px 8px; border-radius:20px; font:600 9px 'Inter',sans-serif; background:#FEE2E2; color:#991B1B;">OVERDUE</span> @endif
                            </div>
                            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                                @if($task->due_at) <span style="font:11px var(--mono); color:{{ $task->isOverdue() ? '#991B1B' : 'var(--ink-3)' }};">⏰ {{ $task->due_at->format('d M Y, H:i') }}</span> @endif
                                @if($task->assignedUser) <span style="font-size:11px; color:var(--ink-3);">👤 {{ $task->assignedUser->name }}</span> @endif
                                @if($task->related_type) <span style="font-size:11px; color:var(--ink-3);">🔗 {{ ucfirst($task->related_type) }} #{{ $task->related_id }}</span> @endif
                            </div>
                            @if($task->description) <p style="font-size:12px; color:var(--ink-2); margin:6px 0 0; line-height:1.4;">{{ $task->description }}</p> @endif
                        </div>
                    </div>
                    <div style="display:flex; gap:6px; flex-shrink:0;">
                        <button wire:click="openEdit({{ $task->id }})"
                            style="padding:5px 10px; border:1px solid var(--rule); border-radius:6px; font:12px 'Inter',sans-serif; color:var(--ink-2); background:var(--canvas); cursor:pointer;">Edit</button>
                        <button x-data="livewireConfirm"
                            @click="confirmAction({ id:{{ $task->id }}, method:'delete', title:'Delete task?', text:'This cannot be undone.' })"
                            style="padding:5px 10px; border:1px solid #FCA5A5; border-radius:6px; font:12px 'Inter',sans-serif; color:#991B1B; background:#FEF2F2; cursor:pointer;">Del</button>
                    </div>
                </div>
            </div>
            @empty
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:48px; text-align:center; color:var(--ink-3); font-size:14px;">
                No tasks found.
            </div>
            @endforelse
        </div>

        @if($tasks->hasPages())
        <div style="margin-top:16px;">{{ $tasks->links() }}</div>
        @endif
    </div>

    {{-- ─── DRAWER ──────────────────────────────────────────────────── --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute; inset:0; background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative; width:480px; height:100vh; background:var(--paper); overflow-y:auto; box-shadow:-4px 0 24px rgba(0,0,0,.12);">
            <div style="padding:20px 24px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:var(--paper); z-index:10;">
                <span style="font:600 16px 'Inter',sans-serif;" x-text="$wire.editingId ? 'Edit Task' : 'New Task'"></span>
                <button @click="$wire.closeDrawer()" style="background:none; border:none; cursor:pointer; color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="padding:24px;">
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
                        <input wire:model="tDueAt" type="datetime-local" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Assigned To</label>
                        <select wire:model="tAssignedTo" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                            <option value="">— Select —</option>
                            @foreach($users as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Related To</label>
                        <select wire:model="tRelatedType" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                            <option value="lead">Lead</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div>
                        <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Related Lead</label>
                        <select wire:model="tRelatedId" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; box-sizing:border-box;">
                            <option value="">— Select —</option>
                            @foreach($leads as $lead) <option value="{{ $lead->id }}">{{ $lead->lead_no }} – {{ $lead->name }}</option> @endforeach
                        </select>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Description</label>
                        <textarea wire:model="tDescription" rows="3" style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); outline:none; resize:vertical; box-sizing:border-box;"></textarea>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; gap:8px; justify-content:flex-end;">
                    <button @click="$wire.closeDrawer()" style="padding:8px 18px; border:1px solid var(--rule); border-radius:7px; font:600 12px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-2); cursor:pointer;">Cancel</button>
                    <button wire:click="save" style="padding:8px 18px; background:var(--ink-1); color:white; border:none; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">Save</button>
                </div>
            </div>
        </div>
    </div>

</div>
