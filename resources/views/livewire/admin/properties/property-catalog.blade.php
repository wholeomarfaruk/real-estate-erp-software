<div
    x-data="{
        q: @entangle('search').live,
        statusFilter: @entangle('statusFilter').live,
        showForm: @entangle('showForm')
    }"
    x-init="$store.pageName = { name: 'Properties', slug: 'properties' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--av-bg:#D2E7D5;--av-fg:#1F5A2C;--bk-bg:#F7E6C4;--bk-fg:#7A5418;--sd-bg:#D8E4F5;--sd-fg:#1F3D72;--rt-bg:#DCD9F2;--rt-fg:#3A3582;--in-bg:#EFEAE0;--in-fg:#5C5648"
>
<style>
  .re-card { background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden; transition:box-shadow .15s; }
  .re-card:hover { box-shadow:0 12px 32px -20px rgba(0,0,0,.2); }
  .kpi-strip-re { display:grid; grid-template-columns:repeat(5,1fr); gap:1px; background:var(--rule); border:1px solid var(--rule); border-radius:10px; overflow:hidden; }
  .kpi-re { background:var(--paper); padding:14px 18px; }
  .status-pill-re { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:10px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
  .occ-bar-re { display:flex; height:8px; border-radius:4px; overflow:hidden; background:var(--canvas); border:1px solid var(--rule); }
  .occ-bar-re span { display:block; height:100%; }
  .bd-re { background:var(--paper); padding:10px 12px; }
</style>

{{-- ─── breadcrumb ───────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-1.5 text-xs mb-4" style="color:var(--ink-3); font-family:'IBM Plex Mono',monospace">
    <span>Real Estate</span>
    <span style="opacity:.5">/</span>
    <span style="color:var(--ink-1); font-weight:600;">Properties</span>
</div>

{{-- ─── page head ────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap justify-between items-end gap-4 mb-5">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight" style="color:var(--ink-1)">Properties</h1>
        <p class="text-sm mt-1" style="color:var(--ink-2)">All registered properties, their floors and unit inventory.</p>
    </div>
    @can('property.create')
    <button wire:click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-semibold" style="background:var(--ink-1);color:var(--paper);border:1px solid var(--ink-1)">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        New Property
    </button>
    @endcan
</div>

{{-- ─── KPI strip ────────────────────────────────────────────────────────── --}}
<div class="kpi-strip-re mb-5">
    @php
        $total = $kpi['total']; $av = $kpi['available']; $bk = $kpi['booked']; $sd = $kpi['sold']; $rt = $kpi['rented'];
        $pct = fn($n,$d) => $d ? round(100*$n/$d) : 0;
        $avPct = $pct($av,$total); $bkPct = $pct($bk,$total); $sdPct = $pct($sd,$total); $rtPct = $pct($rt,$total);
        $totalVal = $kpi['v_available'] + $kpi['v_booked'] + $kpi['v_sold'] + $kpi['v_rented'];
    @endphp
    <div class="kpi-re">
        <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:var(--ink-3)">Properties</div>
        <div class="text-2xl font-semibold" style="font-family:'IBM Plex Mono',monospace">{{ $kpi['properties'] }}</div>
        <div class="text-xs mt-1" style="color:var(--ink-2);font-family:'IBM Plex Mono',monospace">{{ $kpi['active'] }} active · {{ $kpi['properties'] - $kpi['active'] }} inactive</div>
    </div>
    <div class="kpi-re">
        <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:var(--ink-3)">Total Units</div>
        <div class="text-2xl font-semibold" style="font-family:'IBM Plex Mono',monospace">{{ $total }}</div>
        <div class="text-xs mt-1" style="color:var(--ink-2);font-family:'IBM Plex Mono',monospace">across {{ $kpi['floors'] }} floors</div>
    </div>
    <div class="kpi-re">
        <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:var(--ink-3)">Available</div>
        <div class="text-2xl font-semibold" style="font-family:'IBM Plex Mono',monospace;color:var(--av-fg)">{{ $av }}</div>
        <div class="text-xs mt-1" style="color:var(--av-fg);font-family:'IBM Plex Mono',monospace">{{ $avPct }}% of inventory</div>
    </div>
    <div class="kpi-re">
        <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:var(--ink-3)">Booked / Sold / Rented</div>
        <div class="text-xl font-semibold mt-1" style="font-family:'IBM Plex Mono',monospace">
            <span style="color:var(--bk-fg)">{{ $bk }}</span>
            <span style="color:var(--ink-3);font-size:13px">/</span>
            <span style="color:var(--sd-fg)">{{ $sd }}</span>
            <span style="color:var(--ink-3);font-size:13px">/</span>
            <span style="color:var(--rt-fg)">{{ $rt }}</span>
        </div>
        <div class="occ-bar-re mt-2">
            <span style="width:{{ $avPct }}%;background:var(--av-fg)"></span>
            <span style="width:{{ $bkPct }}%;background:var(--bk-fg)"></span>
            <span style="width:{{ $sdPct }}%;background:var(--sd-fg)"></span>
            <span style="width:{{ $rtPct }}%;background:var(--rt-fg)"></span>
        </div>
    </div>
    <div class="kpi-re">
        <div class="text-xs font-semibold uppercase tracking-widest mb-1" style="color:var(--ink-3)">Inventory Value</div>
        <div class="text-xl font-semibold" style="font-family:'IBM Plex Mono',monospace">৳ {{ number_format($totalVal, 0) }}</div>
        <div class="text-xs mt-1" style="color:var(--ink-2);font-family:'IBM Plex Mono',monospace">avail ৳ {{ number_format($kpi['v_available'], 0) }}</div>
    </div>
</div>

{{-- ─── filters ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-3 p-2.5 rounded-xl mb-4 border" style="background:var(--paper);border-color:var(--rule)">
    <div class="flex-1 flex items-center gap-2 px-3 h-9 rounded-md border" style="background:var(--canvas);border-color:transparent">
        <svg class="w-3.5 h-3.5 flex-shrink-0" style="color:var(--ink-3)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="M21 21l-4.3-4.3" stroke-width="2"/></svg>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, code or location…"
            class="w-full bg-transparent border-0 outline-none text-sm" style="color:var(--ink-1)">
    </div>
    <div class="flex gap-1 rounded-md p-0.5" style="background:var(--canvas)">
        @foreach([['all','All'],['active','Active'],['inactive','Inactive']] as [$val,$label])
        <button wire:click="$set('statusFilter','{{ $val }}')"
            class="px-3 py-1.5 rounded text-xs font-medium transition"
            style="{{ $statusFilter === $val ? 'background:var(--paper);color:var(--ink-1);box-shadow:0 1px 2px rgba(0,0,0,.05)' : 'color:var(--ink-2)' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>
</div>

{{-- ─── property grid ────────────────────────────────────────────────────── --}}
@if($properties->isEmpty())
<div class="text-center py-16" style="color:var(--ink-3)">
    <p class="text-sm font-medium">No properties found.</p>
    <p class="text-xs mt-1">Adjust filters or create a new property.</p>
</div>
@else
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @foreach($properties as $prop)
    @php
        $pTotal = $prop->total_units ?? 0;
        $pAv = $prop->available_count ?? 0; $pBk = $prop->booked_count ?? 0;
        $pSd = $prop->sold_count ?? 0; $pRt = $prop->rented_count ?? 0;
        $occ = $pTotal ? round(100*($pBk+$pSd+$pRt)/$pTotal) : 0;
        $avW = $pTotal ? round(100*$pAv/$pTotal) : 0;
        $bkW = $pTotal ? round(100*$pBk/$pTotal) : 0;
        $sdW = $pTotal ? round(100*$pSd/$pTotal) : 0;
        $rtW = $pTotal ? round(100*$pRt/$pTotal) : 0;
        $engName = $prop->engineer?->name ?? '—';
        $engInitials = collect(explode(' ', $engName))->map(fn($w)=>strtoupper(substr($w,0,1)))->take(2)->implode('');
    @endphp
    <article class="re-card flex flex-col">
        {{-- card head --}}
        <header class="flex justify-between items-start gap-3 px-5 py-4 border-b" style="border-color:var(--rule)">
            <div class="flex-1 min-w-0">
                <div class="text-xs font-medium uppercase tracking-wider" style="color:var(--ink-3);font-family:'IBM Plex Mono',monospace">
                    {{ $prop->code }} · {{ $prop->type ?? $prop->property_type ?? '—' }}
                </div>
                <div class="text-base font-semibold mt-1" style="color:var(--ink-1)">{{ $prop->name }}</div>
                @if($prop->address)
                <div class="text-xs mt-1 leading-relaxed" style="color:var(--ink-2)">{{ $prop->address }}</div>
                @endif
            </div>
            <span class="status-pill-re flex-shrink-0 {{ $prop->status === 'active' ? 'text-green-800' : '' }}"
                style="{{ $prop->status === 'active' ? 'background:var(--av-bg);color:var(--av-fg)' : 'background:var(--in-bg);color:var(--in-fg)' }}">
                <span class="w-1.5 h-1.5 rounded-full" style="{{ $prop->status === 'active' ? 'background:var(--av-fg)' : 'background:var(--in-fg)' }}"></span>
                {{ ucfirst($prop->status) }}
            </span>
        </header>

        {{-- occupancy bar --}}
        <div class="px-5 pt-4 pb-2">
            <div class="flex justify-between items-baseline mb-1.5">
                <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ink-3)">Occupancy</span>
                <span class="text-xs font-semibold" style="font-family:'IBM Plex Mono',monospace">{{ $occ }}%</span>
            </div>
            <div class="occ-bar-re">
                <span style="width:{{ $avW }}%;background:var(--av-fg)"></span>
                <span style="width:{{ $bkW }}%;background:var(--bk-fg)"></span>
                <span style="width:{{ $sdW }}%;background:var(--sd-fg)"></span>
                <span style="width:{{ $rtW }}%;background:var(--rt-fg)"></span>
            </div>
            <div class="flex gap-3 mt-1.5 flex-wrap text-xs" style="color:var(--ink-2);font-family:'IBM Plex Mono',monospace">
                <span><i class="inline-block w-2 h-2 rounded-sm mr-1" style="background:var(--av-fg)"></i>{{ $pAv }} available</span>
                <span><i class="inline-block w-2 h-2 rounded-sm mr-1" style="background:var(--bk-fg)"></i>{{ $pBk }} booked</span>
                <span><i class="inline-block w-2 h-2 rounded-sm mr-1" style="background:var(--sd-fg)"></i>{{ $pSd }} sold</span>
                <span><i class="inline-block w-2 h-2 rounded-sm mr-1" style="background:var(--rt-fg)"></i>{{ $pRt }} rented</span>
            </div>
        </div>

        {{-- floors & value --}}
        <div class="flex justify-between items-center px-5 pt-3 pb-4 border-t mt-3" style="border-color:var(--rule)">
            <div>
                <div class="text-xs uppercase tracking-wider font-semibold" style="color:var(--ink-3)">Floors</div>
                <div class="text-sm mt-0.5" style="font-family:'IBM Plex Mono',monospace">{{ $prop->floor_count ?? 0 }} floors · {{ $pTotal }} units</div>
            </div>
            <div class="text-right">
                <div class="text-xs uppercase tracking-wider font-semibold" style="color:var(--ink-3)">Available Value</div>
                <div class="text-sm font-semibold mt-0.5" style="font-family:'IBM Plex Mono',monospace">৳ {{ number_format((float)$prop->available_value, 0) }}</div>
            </div>
        </div>

        {{-- footer: engineer + actions --}}
        <footer class="flex items-center justify-between px-5 py-3 border-t" style="border-color:var(--rule);background:rgba(0,0,0,.015)">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    style="background:var(--accent);color:var(--paper);font-family:'IBM Plex Mono',monospace">
                    {{ $engInitials ?: '?' }}
                </span>
                <div class="flex flex-col leading-tight">
                    <span class="text-xs uppercase tracking-wider font-semibold" style="color:var(--ink-3)">Engineer</span>
                    <span class="text-xs font-medium" style="color:var(--ink-1)">{{ $engName }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('property.edit')
                <button wire:click="openEdit({{ $prop->id }})"
                    class="px-3 py-1.5 rounded text-xs font-medium border transition hover:opacity-80"
                    style="border-color:var(--rule);background:var(--paper);color:var(--ink-2)">
                    Edit
                </button>
                @endcan
                <a href="{{ route('admin.properties.show', $prop) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-semibold"
                    style="background:var(--ink-1);color:var(--paper)">
                    View details
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
            </div>
        </footer>
    </article>
    @endforeach
</div>
@endif

{{-- ─── Property form modal ──────────────────────────────────────────────── --}}
<div x-show="showForm" x-transition.opacity style="display:none"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div @click.stop class="w-full max-w-lg rounded-xl shadow-2xl" style="background:var(--paper)" x-transition>
        <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color:var(--rule)">
            <h2 class="text-base font-semibold" style="color:var(--ink-1)">
                {{ $editingId ? 'Edit Property' : 'New Property' }}
            </h2>
            <button wire:click="closeForm" class="text-gray-400 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            {{-- Auto-populate notification --}}
            @if($selectedProject)
                <div class="p-3 rounded-lg" style="background:rgba(59, 130, 246, 0.1);border:1px solid rgba(59, 130, 246, 0.3)">
                    <p class="text-xs" style="color:#1e40af">
                        <strong>✓ Data auto-filled</strong> from <strong>{{ $selectedProject->name }}</strong>. You can edit any field.
                    </p>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">Project</label>
                <select wire:model.live="fProjectId" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule);background:var(--paper);color:var(--ink-1)">
                    <option value="">— Not linked to a project —</option>
                    @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}{{ $project->code ? ' ('.$project->code.')' : '' }}</option>
                    @endforeach
                    {{-- when editing, show the current linked project even if "taken" --}}
                    @if($editingId && $fProjectId && !$projects->contains('id', $fProjectId))
                        @php $linked = \App\Models\Project::find($fProjectId) @endphp
                        @if($linked)
                        <option value="{{ $linked->id }}" selected>{{ $linked->name }}{{ $linked->code ? ' ('.$linked->code.')' : '' }}</option>
                        @endif
                    @endif
                </select>
                @error('fProjectId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                        Name *
                        @if($selectedProject && $fName) <span style="color:#16a34a;font-weight:normal">(auto-filled)</span> @endif
                    </label>
                    <input wire:model="fName" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule);background:var(--paper);color:var(--ink-1);{{ $selectedProject && $fName ? 'background:rgba(59, 130, 246, 0.08)' : '' }}" placeholder="Shyamnagar Complex">
                    @error('fName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                        Code
                        @if($selectedProject && $fCode) <span style="color:#16a34a;font-weight:normal">(auto-filled)</span> @endif
                    </label>
                    <input wire:model="fCode" type="text" class="w-full rounded-lg border px-3 py-2 text-sm font-mono" style="border-color:var(--rule);background:var(--paper);color:var(--ink-1);{{ $selectedProject && $fCode ? 'background:rgba(59, 130, 246, 0.08)' : '' }}" placeholder="P-101">
                    @error('fCode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                        Type
                        @if($selectedProject && $fType) <span style="color:#16a34a;font-weight:normal">(auto-filled)</span> @endif
                    </label>
                    <input wire:model="fType" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule);{{ $selectedProject && $fType ? 'background:rgba(59, 130, 246, 0.08)' : '' }}" placeholder="Residential / Commercial">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">Status</label>
                    <select wire:model="fStatus" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                    Address
                    @if($selectedProject && $fAddress) <span style="color:#16a34a;font-weight:normal">(auto-filled from project)</span> @endif
                </label>
                <textarea wire:model="fAddress" class="w-full rounded-lg border px-3 py-2 text-sm" rows="2" style="border-color:var(--rule);{{ $selectedProject && $fAddress ? 'background:rgba(59, 130, 246, 0.08)' : '' }}"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                        Total Area (sft)
                        @if($selectedProject && $fTotalArea) <span style="color:#16a34a;font-weight:normal">(auto-filled)</span> @endif
                    </label>
                    <input wire:model="fTotalArea" type="number" step="0.01" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule);{{ $selectedProject && $fTotalArea ? 'background:rgba(59, 130, 246, 0.08)' : '' }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                        Land Size (katha)
                        @if($selectedProject && $fLandSize) <span style="color:#16a34a;font-weight:normal">(auto-filled)</span> @endif
                    </label>
                    <input wire:model="fLandSize" type="number" step="0.01" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule);{{ $selectedProject && $fLandSize ? 'background:rgba(59, 130, 246, 0.08)' : '' }}">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">Assigned Engineer</label>
                    <select wire:model="fEngineerId" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)">
                        <option value="">— None —</option>
                        @foreach($engineers as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">Registered Date</label>
                    <input wire:model="fRegisteredAt" type="date" class="w-full rounded-lg border px-3 py-2 text-sm flatpickr-only-date" style="border-color:var(--rule)">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--ink-3)">
                    Remarks
                    @if($selectedProject && $fRemarks) <span style="color:#16a34a;font-weight:normal">(auto-filled from project)</span> @endif
                </label>
                <textarea wire:model="fRemarks" class="w-full rounded-lg border px-3 py-2 text-sm" rows="2" style="border-color:var(--rule);{{ $selectedProject && $fRemarks ? 'background:rgba(59, 130, 246, 0.08)' : '' }}"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t" style="border-color:var(--rule);background:rgba(0,0,0,.012)">
            <button wire:click="closeForm" class="px-4 py-2 rounded-md text-sm border" style="border-color:var(--rule)">Cancel</button>
            <button wire:click="save" class="px-4 py-2 rounded-md text-sm font-semibold" style="background:var(--ink-1);color:var(--paper)">
                Save Property
            </button>
        </div>
    </div>
</div>

</div>
