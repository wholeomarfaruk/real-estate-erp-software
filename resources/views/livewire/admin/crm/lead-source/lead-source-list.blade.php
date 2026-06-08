<div
    x-data="{ drawerOpen: $wire.entangle('drawerOpen') }"
    x-init="$store.pageName = { name: 'Lead Sources', slug: 'lead-sources' }"
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
                <span style="color:var(--ink-1);">Lead Sources</span>
            </div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Lead Sources</h1>
            <p style="margin-top:4px; font-size:13px; color:var(--ink-2);">Manage where your leads come from.</p>
        </div>
        <button @click="$wire.openCreate()"
            style="background:var(--ink-1); color:var(--paper); border:none; padding:8px 16px; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Source
        </button>
    </div>

    <div style="padding:20px 24px 80px;">
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:var(--canvas); border-bottom:1px solid var(--rule);">
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Name</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Color</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Leads</th>
                        <th style="padding:10px 16px; text-align:left; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Status</th>
                        <th style="padding:10px 16px; text-align:right; font:600 10px 'Inter',sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sources as $src)
                    <tr style="border-bottom:1px solid var(--rule);" class="hover:bg-gray-50">
                        <td style="padding:12px 16px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:10px; height:10px; border-radius:50%; background:{{ $src->color }}; flex-shrink:0;"></div>
                                <span style="font-weight:600; font-size:13px;">{{ $src->name }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span style="font:11px var(--mono); color:var(--ink-3);">{{ $src->color }}</span>
                        </td>
                        <td style="padding:12px 16px; font:600 13px var(--mono);">{{ $src->leads_count }}</td>
                        <td style="padding:12px 16px;">
                            <span style="padding:3px 9px; border-radius:20px; font:600 10px 'Inter',sans-serif;
                                         {{ $src->is_active ? 'background:#D1FAE5; color:#065F46;' : 'background:#F3F4F6; color:#374151;' }}">
                                {{ $src->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="padding:12px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                <button wire:click="openEdit({{ $src->id }})"
                                    style="padding:5px 10px; border:1px solid var(--rule); border-radius:6px; font:12px 'Inter',sans-serif; color:var(--ink-2); background:var(--canvas); cursor:pointer;">
                                    Edit
                                </button>
                                <button x-data="livewireConfirm"
                                    @click="confirmAction({ id:{{ $src->id }}, method:'delete', title:'Delete source?', text:'All associated leads will lose this source.' })"
                                    style="padding:5px 10px; border:1px solid #FCA5A5; border-radius:6px; font:12px 'Inter',sans-serif; color:#991B1B; background:#FEF2F2; cursor:pointer;">
                                    Del
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:48px; text-align:center; color:var(--ink-3); font-size:14px;">
                            No lead sources yet. <button @click="$wire.openCreate()" style="background:none; border:none; color:var(--accent); cursor:pointer; text-decoration:underline;">Add your first source.</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($sources->hasPages())
            <div style="padding:12px 16px; border-top:1px solid var(--rule); background:var(--canvas);">{{ $sources->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ─── DRAWER ──────────────────────────────────────────────────────── --}}
    <div x-show="drawerOpen" x-cloak style="position:fixed; inset:0; z-index:50; display:flex; justify-content:flex-end;">
        <div @click="$wire.closeDrawer()" style="position:absolute; inset:0; background:rgba(0,0,0,.4);"></div>
        <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full"
             style="position:relative; width:380px; height:100vh; background:var(--paper); overflow-y:auto; box-shadow:-4px 0 24px rgba(0,0,0,.12);">
            <div style="padding:20px 24px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; background:var(--paper); z-index:10;">
                <span style="font:600 16px 'Inter',sans-serif;" x-text="$wire.editingId ? 'Edit Source' : 'New Source'"></span>
                <button @click="$wire.closeDrawer()" style="background:none; border:none; cursor:pointer; color:var(--ink-3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div style="padding:24px;">
                <div style="margin-bottom:16px;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Name <span style="color:#EF4444;">*</span></label>
                    <input wire:model="fName" type="text" placeholder="e.g. Facebook Ads"
                        style="width:100%; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                    @error('fName') <p style="color:#EF4444; font-size:11px; margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div style="margin-bottom:16px;">
                    <label style="font:600 11px 'Inter',sans-serif; color:var(--ink-2); display:block; margin-bottom:5px;">Color</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input wire:model="fColor" type="color" style="width:44px; height:36px; border:1px solid var(--rule); border-radius:7px; cursor:pointer; padding:2px;">
                        <input wire:model="fColor" type="text" placeholder="#6B7280"
                            style="flex:1; padding:8px 12px; border:1px solid var(--rule); border-radius:7px; font:13px var(--mono); background:var(--canvas); color:var(--ink-1); outline:none; box-sizing:border-box;">
                    </div>
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input wire:model="fIsActive" type="checkbox"
                            style="width:16px; height:16px; accent-color:var(--accent); cursor:pointer;">
                        <span style="font:500 13px 'Inter',sans-serif; color:var(--ink-2);">Active</span>
                    </label>
                </div>
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <button @click="$wire.closeDrawer()"
                        style="padding:8px 18px; border:1px solid var(--rule); border-radius:7px; font:600 12px 'Inter',sans-serif; background:var(--canvas); color:var(--ink-2); cursor:pointer;">Cancel</button>
                    <button wire:click="save"
                        style="padding:8px 18px; background:var(--ink-1); color:white; border:none; border-radius:7px; font:600 12px 'Inter',sans-serif; cursor:pointer;">Save</button>
                </div>
            </div>
        </div>
    </div>

</div>
