<div x-data="{
    drawerOpen: @entangle('drawerOpen'),
    floorFormOpen: @entangle('floorFormOpen'),
    typeModalOpen: @entangle('typeModalOpen'),
    editMode: false,
    dragFloor: null,
    dragUnit: null,
    dragUnitFloor: null,
    csrfToken() {
        return document.querySelector('meta[name=csrf-token]')?.content ?? '';
    },
    reorderFloors(orderedIds) {
        fetch('{{ route('admin.properties.floors.reorder', $property) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            body: JSON.stringify({ order: orderedIds })
        }).then(r => {
            if (r.ok) $wire.reloadBuildingWithToast('Section order saved.');
            else r.text().then(t => console.error('Floor reorder failed:', r.status, t));
        }).catch(e => console.error('Floor reorder error:', e));
    },
    reorderUnits(floors) {
        fetch('{{ route('admin.properties.units.reorder', $property) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
            body: JSON.stringify({ floors })
        }).then(r => {
            if (r.ok) $wire.reloadBuildingWithToast('Unit order saved.');
            else r.text().then(t => console.error('Unit reorder failed:', r.status, t));
        }).catch(e => console.error('Unit reorder error:', e));
    }
}" x-init="$store.pageName = { name: 'Property Detail', slug: 'property-detail' }"
    style="--paper:#FCFBF7;--canvas:#F2EFE7;--ink-1:#1A1814;--ink-2:#5C5648;--ink-3:#9B9686;--rule:#EAE5D9;--accent:#1F3A68;--av-bg:#D2E7D5;--av-fg:#1F5A2C;--bk-bg:#F7E6C4;--bk-fg:#7A5418;--sd-bg:#D8E4F5;--sd-fg:#1F3D72;--rt-bg:#DCD9F2;--rt-fg:#3A3582;--in-bg:#EFEAE0;--in-fg:#5C5648">
    <style>
        .re-floor {
            display: grid;
            grid-template-columns: 84px 1fr 180px;
            gap: 0;
            align-items: stretch;
            border: 1px solid var(--rule);
            border-radius: 10px;
            overflow: hidden;
            background: var(--paper);
            transition: box-shadow .15s, opacity .15s;
        }

        .re-floor:hover {
            box-shadow: 0 8px 24px -16px rgba(0, 0, 0, .15);
        }

        .re-floor.dragging {
            opacity: .35;
        }

        .re-floor.drop-above {
            box-shadow: 0 -3px 0 0 var(--ink-1);
        }

        .re-floor.drop-below {
            box-shadow: 0 3px 0 0 var(--ink-1);
        }

        .re-unit {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 88px;
            height: 64px;
            border-radius: 7px;
            padding: 7px 9px;
            cursor: grab;
            border: 1px solid transparent;
            transition: transform .12s, box-shadow .15s, opacity .15s;
            user-select: none;
        }

        .re-unit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -8px rgba(0, 0, 0, .2);
        }

        .re-unit:active {
            cursor: grabbing;
        }

        .re-unit.dragging {
            opacity: .35;
        }

        .re-unit.drop-left {
            box-shadow: -3px 0 0 0 var(--ink-1);
        }

        .re-unit.drop-right {
            box-shadow: 3px 0 0 0 var(--ink-1);
        }

        .re-unit.av {
            background: var(--av-bg);
            color: var(--av-fg);
        }

        .re-unit.bk {
            background: var(--bk-bg);
            color: var(--bk-fg);
        }

        .re-unit.sd {
            background: var(--sd-bg);
            color: var(--sd-fg);
        }

        .re-unit.rt {
            background: var(--rt-bg);
            color: var(--rt-fg);
        }

        .seg-radio {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
        }

        .seg-radio label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 9px 0;
            border: 1px solid var(--rule);
            border-radius: 7px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: var(--paper);
            color: var(--ink-2);
            transition: background .12s, border-color .12s;
        }

        .seg-radio input {
            display: none;
        }

        .kpi-strip-d {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1px;
            background: var(--rule);
            border: 1px solid var(--rule);
            border-radius: 10px;
            overflow: hidden;
        }

        .kpi-d {
            background: var(--paper);
            padding: 12px 14px;
        }
    </style>

    {{-- breadcrumb --}}
    <div class="flex items-center gap-1.5 text-xs mb-4" style="color:var(--ink-3);font-family:'IBM Plex Mono',monospace">
        <a href="{{ route('admin.properties.index') }}" class="hover:opacity-70 transition" style="color:var(--ink-3)">Real
            Estate</a>
        <span style="opacity:.5">/</span>
        <a href="{{ route('admin.properties.index') }}" class="hover:opacity-70 transition"
            style="color:var(--ink-3)">Properties</a>
        <span style="opacity:.5">/</span>
        <span style="color:var(--ink-1);font-weight:600">{{ $property->code }} · {{ $property->name }}</span>
    </div>

    {{-- ─── HERO ─────────────────────────────────────────────────────────────── --}}
    <section class="rounded-xl border mb-4 overflow-hidden grid md:grid-cols-2"
        style="background:var(--paper);border-color:var(--rule)">

        {{-- photo side / slider --}}
        @php $sliderImages = $propertyImageFiles; @endphp
        <div class="flex flex-col min-h-72" x-data="{
            active: 0,
            total: {{ $sliderImages->count() }},
            prev() { this.active = this.active === 0 ? this.total - 1 : this.active - 1; },
            next() { this.active = this.active === this.total - 1 ? 0 : this.active + 1; }
        }">

            {{-- main slide area --}}
            <div class="flex-1 relative overflow-hidden"
                style="background:linear-gradient(135deg,#8a7a5a,#5c4f38);min-height:240px">

                @if ($sliderImages->isEmpty())
                    <div class="absolute inset-0 flex flex-col items-center justify-center"
                        style="color:rgba(255,255,255,.7)">
                        <svg class="w-16 h-16 opacity-40" fill="none" stroke="currentColor" stroke-width="1.2"
                            viewBox="0 0 24 24">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-widest opacity-60">No Photos</p>
                        @can('property.edit')
                            <button x-show="editMode"
                                @click="$dispatch('openMediaPicker',{target:'propertyImages',multiple:true,type:'image'})"
                                class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-semibold"
                                style="background:rgba(255,255,255,.2);color:#fff">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Photos
                            </button>
                        @endcan
                    </div>
                @else
                    @foreach ($sliderImages as $i => $img)
                        <div x-show="active === {{ $i }}"
                            x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100" class="absolute inset-0"
                            style="{{ $i === 0 ? '' : 'display:none' }}">
                            <img src="{{ file_path($img->id) }}" alt="{{ $img->name }}"
                                class="w-full h-full object-cover">
                        </div>
                    @endforeach

                    @if ($sliderImages->count() > 1)
                        <button @click="prev()"
                            class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center transition hover:scale-110"
                            style="background:rgba(0,0,0,.45);color:#fff;backdrop-filter:blur(4px)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path d="M15 18l-6-6 6-6" />
                            </svg>
                        </button>
                        <button @click="next()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center transition hover:scale-110"
                            style="background:rgba(0,0,0,.45);color:#fff;backdrop-filter:blur(4px)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
                            @foreach ($sliderImages as $i => $img)
                                <button @click="active = {{ $i }}" class="rounded-full transition-all"
                                    :style="active === {{ $i }} ? 'width:18px;height:6px;background:#fff' :
                                        'width:6px;height:6px;background:rgba(255,255,255,.5)'">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- code badge + add button --}}
                <div class="absolute top-3 left-3 flex items-center gap-2">
                    <span class="px-2 py-1 rounded-md text-xs font-medium"
                        style="background:rgba(0,0,0,.55);color:#fff;font-family:'IBM Plex Mono',monospace;backdrop-filter:blur(8px)">
                        {{ $property->code }}
                    </span>
                    @can('property.edit')
                        @if ($sliderImages->isNotEmpty())
                            <button x-show="editMode"
                                @click="$dispatch('openMediaPicker',{target:'propertyImages',multiple:true,type:'image'})"
                                class="px-2 py-1 rounded-md text-xs font-medium transition"
                                style="background:rgba(0,0,0,.45);color:#fff;backdrop-filter:blur(4px)">
                                + Add
                            </button>
                        @endif
                    @endcan
                </div>

                @if ($sliderImages->count() > 1)
                    <div class="absolute top-3 right-3 px-2 py-1 rounded-md text-xs font-medium"
                        style="background:rgba(0,0,0,.45);color:#fff;font-family:'IBM Plex Mono',monospace;backdrop-filter:blur(4px)">
                        <span x-text="active + 1"></span>/{{ $sliderImages->count() }}
                    </div>
                @endif
            </div>

            {{-- thumbnail strip --}}
            @if ($sliderImages->isNotEmpty())
                <div class="flex gap-1.5 p-2 border-t overflow-x-auto"
                    style="background:var(--canvas);border-color:var(--rule)">
                    @foreach ($sliderImages as $i => $img)
                        <button @click="active = {{ $i }}"
                            class="w-16 h-12 rounded-md overflow-hidden flex-shrink-0 border-2 transition-all"
                            :style="active === {{ $i }} ? 'border-color:var(--accent);opacity:1' :
                                'border-color:transparent;opacity:.65'">
                            <img src="{{ file_path($img->id) }}" alt="{{ $img->name }}"
                                class="w-full h-full object-cover">
                        </button>
                    @endforeach
                    @can('property.edit')
                        <button x-show="editMode" wire:click="savePropertyImages" title="Save slider images"
                            class="w-12 h-12 rounded-md flex-shrink-0 flex flex-col items-center justify-center gap-0.5 border-2 border-dashed text-xs transition hover:opacity-100 opacity-60"
                            style="border-color:var(--rule);color:var(--ink-3)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                                <polyline points="17 21 17 13 7 13 7 21" />
                                <polyline points="7 3 7 8 15 8" />
                            </svg>
                            Save
                        </button>
                    @endcan
                </div>
            @endif
        </div>

        {{-- info side --}}
        <div class="p-6 flex flex-col" style="border-left:1px solid var(--rule)">
            <div class="text-xs font-medium uppercase tracking-wider"
                style="color:var(--ink-3);font-family:'IBM Plex Mono',monospace">
                {{ $property->code }}
            </div>
            <h1 class="text-2xl font-semibold mt-1.5 leading-tight" style="color:var(--ink-1);letter-spacing:-.01em">
                {{ $property->name }}</h1>
            @if ($property->address)
                <p class="text-sm mt-1.5 leading-relaxed" style="color:var(--ink-2)">{{ $property->address }}</p>
            @endif

            <div class="flex flex-wrap gap-1.5 mt-3">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider"
                    style="{{ $property->status === 'active' ? 'background:var(--av-bg);color:var(--av-fg)' : 'background:var(--in-bg);color:var(--in-fg)' }}">
                    <span class="w-1.5 h-1.5 rounded-full"
                        style="{{ $property->status === 'active' ? 'background:var(--av-fg)' : 'background:var(--in-fg)' }}"></span>
                    {{ ucfirst($property->status) }}
                </span>
                @php
                    $types = $property->type ?? ($property->property_type ? [$property->property_type] : []);
                    $typeArray = is_array($types) ? $types : [$types];
                @endphp
                @foreach ($typeArray as $type)
                    @if ($type)
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider"
                            style="background:var(--in-bg);color:var(--in-fg)">
                            {{ $type }}
                        </span>
                    @endif
                @endforeach
            </div>

            {{-- facts grid --}}
            <div class="mt-4 grid grid-cols-2 gap-px rounded-lg overflow-hidden border"
                style="border-color:var(--rule)">
                @php
                    $facts = [
                        [
                            'Total Area',
                            $property->total_area ? number_format($property->total_area, 2) . ' sft' : '—',
                            true,
                        ],
                        [
                            'Land Size',
                            $property->land_size ? number_format($property->land_size, 2) . ' katha' : '—',
                            true,
                        ],
                        ['Sections', $kpi['floors'], true],
                        ['Total Units', $kpi['total'], true],
                        ['Engineer', $property->engineer?->name ?? '—', false],
                        ['Registered', $property->registered_at?->format('d M, Y') ?? '—', false],
                    ];
                @endphp
                @foreach ($facts as [$lbl, $val, $mono])
                    <div class="px-3 py-2.5"
                        style="background:var(--paper);{{ !$loop->last && !($loop->index % 2 === 1) ? 'border-right:1px solid var(--rule);' : '' }}{{ $loop->index < count($facts) - 2 ? 'border-bottom:1px solid var(--rule);' : '' }}">
                        <div class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ink-3)">
                            {{ $lbl }}</div>
                        <div class="mt-0.5 text-sm font-medium"
                            style="{{ $mono ? 'font-family:\'IBM Plex Mono\',monospace' : '' }};color:var(--ink-1)">
                            {{ $val }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-auto pt-4 flex gap-2">
                <a href="{{ route('admin.properties.index') }}"
                    class="px-3 py-1.5 rounded-md text-xs font-medium border" style="border-color:var(--rule)">←
                    Back</a>
                @can('property.edit')
                    <button @click="editMode = !editMode" :class="editMode ? 'text-white' : ''"
                        :style="editMode ? 'background:var(--accent);border-color:var(--accent)' : 'border-color:var(--rule)'"
                        class="px-3 py-1.5 rounded-md text-xs font-medium border transition">
                        <span x-text="editMode ? 'Done Editing' : 'Edit'">Edit</span>
                    </button>
                @endcan
            </div>
        </div>
    </section>
    @livewire('admin.file.media-picker', ['mediapickerModal' => false], key('media-picker-propertyImages'))

    {{-- ─── KPI STRIP ────────────────────────────────────────────────────────── --}}
    <div class="kpi-strip-d mb-5">
        @php $kpiItems = [['Units','total','',''],['Available','available','--av-fg',''],['Booked','booked','--bk-fg',''],['Sold','sold','--sd-fg',''],['Rented','rented','--rt-fg',''],['Sections','floors','','']] @endphp
        @foreach ($kpiItems as [$label, $key, $color, $suffix])
            <div class="kpi-d">
                <div class="text-xs font-semibold uppercase tracking-widest" style="color:var(--ink-3)">
                    {{ $label }}</div>
                <div class="text-xl font-semibold mt-1"
                    style="font-family:'IBM Plex Mono',monospace;{{ $color ? 'color:var(' . $color . ')' : '' }}">
                    {{ $kpi[$key] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ─── BUILDING VIEW ────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border p-5 mb-5" style="background:var(--paper);border-color:var(--rule)">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-base font-semibold" style="color:var(--ink-1)">Building View</h2>
                <p class="text-xs mt-0.5" style="color:var(--ink-3)">Drag sections or units to reorder. Click a unit
                    to edit.</p>
            </div>
            @can('property.edit')
                <button x-show="editMode" wire:click="openFloorForm(null)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold border"
                    style="border-color:var(--ink-3);color:var(--ink-2)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Section
                </button>
            @endcan
        </div>

        {{-- floors stack --}}
        <div class="flex flex-col gap-2.5" id="floors-stack">
            @forelse($floors as $floor)
                @php
                    $fTotal = count($floor['units']);
                    $fAv = collect($floor['units'])->where('status', 'available')->count();
                    $fBk = collect($floor['units'])->where('status', 'booked')->count();
                    $fSd = collect($floor['units'])->where('status', 'sold')->count();
                    $fRt = collect($floor['units'])->where('status', 'rented')->count();
                    $fTW = $fTotal ? round((100 * $fAv) / $fTotal) : 0;
                    $fBkW = $fTotal ? round((100 * $fBk) / $fTotal) : 0;
                    $fSdW = $fTotal ? round((100 * $fSd) / $fTotal) : 0;
                    $fRtW = $fTotal ? round((100 * $fRt) / $fTotal) : 0;
                @endphp
                <div class="re-floor" data-floor-id="{{ $floor['id'] }}" :draggable="editMode"
                    @dragstart.self="if(editMode) dragFloor={{ $floor['id'] }}"
                    @dragend.self="if(editMode){ dragFloor=null;$el.classList.remove('dragging') }"
                    @dragstart="if(editMode) $event.currentTarget.classList.add('dragging')"
                    @dragover.prevent="if(editMode) $event.currentTarget.classList.add($event.clientY < $event.currentTarget.getBoundingClientRect().top + $event.currentTarget.offsetHeight/2 ? 'drop-above' : 'drop-below')"
                    @dragleave="$el.classList.remove('drop-above','drop-below')"
                    @drop.prevent="
                $el.classList.remove('drop-above','drop-below');
                if (!editMode || !dragFloor || dragFloor == {{ $floor['id'] }}) return;
                const stack = document.getElementById('floors-stack');
                const dragged = stack.querySelector('[data-floor-id=\''+dragFloor+'\']');
                const above = $event.clientY < $el.getBoundingClientRect().top + $el.offsetHeight/2;
                above ? $el.parentNode.insertBefore(dragged, $el) : $el.parentNode.insertBefore(dragged, $el.nextSibling);
                const ids = [...stack.querySelectorAll('[data-floor-id]')].map(el=>parseInt(el.dataset.floorId));
                reorderFloors(ids);
                dragFloor = null;
            ">
                    {{-- floor badge --}}
                    <div class="flex flex-col items-center justify-center px-2 py-3 relative"
                        :class="editMode ? 'cursor-grab' : 'cursor-default'"
                        style="background:var(--canvas);border-right:1px solid var(--rule)">
                        <svg class="w-3.5 h-3.5 mb-1 opacity-40" fill="none" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24" style="color:var(--ink-3)">
                            <path d="M4 8h16M4 16h16" />
                        </svg>
                        <div class="text-xl font-bold leading-none"
                            style="font-family:'IBM Plex Mono',monospace;color:var(--ink-1)">
                            {{ $floor['code'] ?? substr($loop->iteration, 0, 2) }}
                        </div>
                        <div class="text-xs font-semibold uppercase tracking-widest mt-1" style="color:var(--ink-3)">
                            {{ strlen($floor['label']) > 8 ? substr($floor['label'], 0, 7) . '…' : $floor['label'] }}
                        </div>
                        @can('property.edit')
                            <div x-show="editMode" class="flex gap-1 mt-2">
                                <button wire:click="openFloorForm({{ $floor['id'] }})"
                                    class="w-5 h-5 rounded flex items-center justify-center text-xs opacity-60 hover:opacity-100"
                                    style="color:var(--ink-2)" title="Edit section">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="deleteFloor({{ $floor['id'] }})"
                                    class="w-5 h-5 rounded flex items-center justify-center text-xs opacity-60 hover:opacity-100"
                                    style="color:var(--ink-3)" title="Delete section">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endcan
                    </div>

                    {{-- units strip --}}
                    <div class="flex items-center gap-1.5 flex-wrap p-3 min-h-16"
                        data-units-floor="{{ $floor['id'] }}" @dragover.prevent
                        @drop.prevent="
                    if (!editMode || !dragUnit || !dragUnitFloor) return;
                    const strip = $el;
                    const dragged = document.querySelector('[data-unit-id=\''+dragUnit+'\']');
                    strip.insertBefore(dragged, strip.querySelector('.unit-add-btn'));
                    const floors = {};
                    document.querySelectorAll('[data-units-floor]').forEach(s => {
                        floors[s.dataset.unitsFloor] = [...s.querySelectorAll('[data-unit-id]')].map(u=>parseInt(u.dataset.unitId));
                    });
                    reorderUnits(floors);
                    dragUnit = null; dragUnitFloor = null;
                ">
                        @foreach ($floor['units'] as $unit)
                            @php
                                $cls = match ($unit['status']) {
                                    'available' => 'av',
                                    'booked' => 'bk',
                                    'sold' => 'sd',
                                    'rented' => 'rt',
                                    default => 'av',
                                };
                                $typeIcon = match ($unit['type']) {
                                    'shop' => '🏪',
                                    'parking' => '🚗',
                                    default => '🏠',
                                };
                            @endphp
                            <div class="re-unit {{ $cls }}" data-unit-id="{{ $unit['id'] }}"
                                :draggable="editMode"
                                @dragstart="if(editMode){ dragUnit={{ $unit['id'] }};dragUnitFloor={{ $floor['id'] }};$el.classList.add('dragging') }"
                                @dragend="dragUnit=null;dragUnitFloor=null;$el.classList.remove('dragging')"
                                wire:click="openUnitEdit({{ $unit['id'] }})">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold"
                                        style="font-family:'IBM Plex Mono',monospace">{{ $unit['code'] }}</span>
                                    <span class="text-xs">{{ $typeIcon }}</span>
                                </div>
                                <div class="flex justify-between items-end">
                                    <span class="text-xs font-semibold uppercase tracking-wider"
                                        style="letter-spacing:.04em">{{ $unit['type'] }}</span>
                                   
                                </div>
                            </div>
                        @endforeach

                        @can('property.edit')
                            <button x-show="editMode"
                                class="unit-add-btn w-16 h-16 rounded-lg border-2 border-dashed flex items-center justify-center text-lg font-bold"
                                style="border-color:var(--ink-3);color:var(--ink-2)"
                                wire:click="openUnitAdd({{ $floor['id'] }})">+</button>
                        @endcan
                    </div>

                    {{-- floor summary --}}
                    <div class="flex flex-col justify-center gap-1.5 px-3 py-3 border-l"
                        style="border-color:var(--rule);background:rgba(0,0,0,.012)">
                        <div class="text-sm font-semibold" style="color:var(--ink-1)">{{ $fTotal }} units</div>
                        <div class="flex h-1.5 rounded-full overflow-hidden" style="background:var(--canvas)">
                            <span style="width:{{ $fTW }}%;background:var(--av-fg)"></span>
                            <span style="width:{{ $fBkW }}%;background:var(--bk-fg)"></span>
                            <span style="width:{{ $fSdW }}%;background:var(--sd-fg)"></span>
                            <span style="width:{{ $fRtW }}%;background:var(--rt-fg)"></span>
                        </div>
                        <div class="text-xs" style="color:var(--ink-2);font-family:'IBM Plex Mono',monospace">
                            <span style="color:var(--av-fg)">{{ $fAv }}av</span>
                            <span style="color:var(--bk-fg);margin-left:4px">{{ $fBk }}bk</span>
                            <span style="color:var(--sd-fg);margin-left:4px">{{ $fSd }}sd</span>
                            <span style="color:var(--rt-fg);margin-left:4px">{{ $fRt }}rt</span>
                        </div>
                        @if ($floor['floor_area'])
                            <div class="text-xs" style="color:var(--ink-3);font-family:'IBM Plex Mono',monospace">
                                {{ number_format($floor['floor_area'], 0) }} sft</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-10 text-center" style="color:var(--ink-3)">
                    <p class="text-sm">No sections yet. Add the first section to start building the unit inventory.</p>
                </div>
            @endforelse
        </div>

        {{-- ground hatch --}}
        @if (count($floors))
            <div class="h-3 mt-2 rounded-b-lg opacity-40"
                style="background:repeating-linear-gradient(135deg,var(--ink-3) 0,var(--ink-3) 2px,transparent 2px,transparent 8px)">
            </div>
        @endif

        {{-- legend --}}
        <div class="flex items-center justify-between mt-4 pt-3 border-t border-dashed"
            style="border-color:var(--rule)">
            <div class="flex gap-4 flex-wrap text-xs" style="color:var(--ink-2)">
                <span><i class="inline-block w-2.5 h-2.5 rounded mr-1.5"
                        style="background:var(--av-fg)"></i>Available</span>
                <span><i class="inline-block w-2.5 h-2.5 rounded mr-1.5"
                        style="background:var(--bk-fg)"></i>Booked</span>
                <span><i class="inline-block w-2.5 h-2.5 rounded mr-1.5"
                        style="background:var(--sd-fg)"></i>Sold</span>
                <span><i class="inline-block w-2.5 h-2.5 rounded mr-1.5"
                        style="background:var(--rt-fg)"></i>Rented</span>
            </div>
            <p class="text-xs" style="color:var(--ink-3)">Drag to reorder · Click to edit</p>
        </div>
    </div>

    {{-- ─── UNITS TABLE ──────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border mb-5 overflow-hidden" style="background:var(--paper);border-color:var(--rule)">
        <div class="flex justify-between items-center px-5 py-3 border-b" style="border-color:var(--rule)">
            <div class="flex items-baseline gap-2">
                <h3 class="text-sm font-semibold" style="color:var(--ink-1)">Unit Inventory</h3>
                <span class="text-xs"
                    style="color:var(--ink-3);font-family:'IBM Plex Mono',monospace">{{ $kpi['total'] }} units</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr style="background:rgba(0,0,0,.012);border-bottom:1px solid var(--rule)">
                        <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Code</th>
                        <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Section</th>
                        <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Type</th>
                        <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Status</th>
                        <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Area (sft)</th>
                        <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Price</th>
                        <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-wider"
                            style="color:var(--ink-2)">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="divide-color:var(--rule)">
                    @foreach ($property->floors as $floor)
                        @foreach ($floor->units as $unit)
                            @php
                                $status = $unit->effective_status;
                                $statusCls = match ($status) {
                                    'available' => 'av',
                                    'booked' => 'bk',
                                    'sold' => 'sd',
                                    'rented' => 'rt',
                                    default => 'av',
                                };
                                $statusBg = match ($status) {
                                    'available' => 'background:var(--av-bg);color:var(--av-fg)',
                                    'booked' => 'background:var(--bk-bg);color:var(--bk-fg)',
                                    'sold' => 'background:var(--sd-bg);color:var(--sd-fg)',
                                    'rented' => 'background:var(--rt-bg);color:var(--rt-fg)',
                                    default => '',
                                };
                            @endphp
                            <tr class="hover:bg-black/[.012] transition">
                                <td class="px-4 py-3 font-bold"
                                    style="font-family:'IBM Plex Mono',monospace;color:var(--ink-1)">
                                    {{ $unit->effective_code }}</td>
                                <td class="px-4 py-3" style="color:var(--ink-2)">
                                    {{ $floor->label ?? $floor->floor_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider"
                                        style="background:var(--in-bg);color:var(--in-fg)">
                                        {{ $unit->effective_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider"
                                        style="{{ $statusBg }}">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background:currentColor"></span>
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right"
                                    style="font-family:'IBM Plex Mono',monospace;color:var(--ink-1)">
                                    {{ $unit->effective_area ? number_format($unit->effective_area, 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right"
                                    style="font-family:'IBM Plex Mono',monospace;color:var(--ink-1)">
                                    {{ $unit->effective_price > 0 ? '৳ ' . number_format($unit->effective_price, 0) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        @can('property.edit')
                                            <button x-show="editMode" wire:click="openUnitEdit({{ $unit->id }})"
                                                class="text-xs hover:opacity-100 opacity-60 transition"
                                                style="color:var(--ink-2)">Edit</button>
                                            <button x-show="editMode" wire:click="deleteUnit({{ $unit->id }})"
                                                class="text-xs hover:opacity-100 opacity-60 transition"
                                                style="color:red">Del</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    @if ($kpi['total'] === 0)
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-xs" style="color:var(--ink-3)">No
                                units added yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── GALLERY ──────────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border p-5" style="background:var(--paper);border-color:var(--rule)">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-semibold" style="color:var(--ink-1)">Photos & Documents</h3>
            <div class="flex items-center gap-2">
                @can('property.edit')
                    <button x-show="editMode"
                        @click="$dispatch('openMediaPicker',{target:'documents',multiple:true,type:'all'})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border"
                        style="border-color:var(--rule);color:var(--ink-2)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add
                    </button>
                    <button x-show="editMode" wire:click="saveDocuments"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white"
                        style="background:var(--accent)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        Save
                    </button>
                @endcan
            </div>
        </div>

        @if ($documentFiles->isEmpty())
            <div class="py-10 text-center border-2 border-dashed rounded-lg" style="border-color:var(--rule)">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5"
                    viewBox="0 0 24 24" style="color:var(--ink-3)">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <polyline points="21 15 16 10 5 21" />
                </svg>
                <p class="text-xs" style="color:var(--ink-3)">No photos or documents yet. Click Add to attach files.
                </p>
            </div>
        @else
            {{-- images grid --}}
            @php
                $images = $documentFiles->where('type', 'image');
                $otherFiles = $documentFiles->where('type', '!=', 'image');
            @endphp
            @if ($images->isNotEmpty())
                <div class="grid gap-2.5 mb-4" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr))">
                    @foreach ($images as $img)
                        <div class="relative rounded-lg overflow-hidden group"
                            style="aspect-ratio:4/3;background:linear-gradient(135deg,#8a7a5a,#5c4f38)">
                            <img src="{{ file_path($img->id) }}" alt="{{ $img->name }}"
                                class="w-full h-full object-cover">
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end"
                                style="background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.7))">
                                <div class="flex items-center justify-between px-2 pb-2 pt-4">
                                    <span
                                        class="text-xs text-white/80 truncate max-w-[80px]">{{ $img->name }}</span>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <a href="{{ file_path($img->id) }}" download="{{ $img->name }}"
                                            title="Download"
                                            class="w-6 h-6 rounded flex items-center justify-center transition"
                                            style="background:rgba(255,255,255,.2);color:#fff">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M12 4.5v9m0 0 3.5-3.5M12 13.5 8.5 10M4.5 19.5h15" />
                                            </svg>
                                        </a>
                                        @can('property.edit')
                                            <button x-show="editMode"
                                                wire:click="removeMedia('documents', {{ $img->id }})" title="Delete"
                                                class="w-6 h-6 rounded flex items-center justify-center transition"
                                                style="background:rgba(220,38,38,.7);color:#fff">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- non-image files list --}}
            @if ($otherFiles->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($otherFiles as $file)
                        @php
                            $ext = strtolower($file->extension ?? '');
                            $isPdf = $ext === 'pdf';
                            $isWord = in_array($ext, ['doc', 'docx']);
                            $isExcel = in_array($ext, ['xls', 'xlsx', 'csv']);
                        @endphp
                        <div class="flex items-center justify-between rounded-lg border px-4 py-3 gap-3"
                            style="border-color:var(--rule);background:var(--canvas)">
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- file type icon --}}
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                    style="{{ $isPdf ? 'background:#fee2e2;color:#dc2626' : ($isWord ? 'background:#dbeafe;color:#1d4ed8' : ($isExcel ? 'background:#dcfce7;color:#16a34a' : 'background:#f3f4f6;color:#374151')) }}">
                                    @if ($isPdf)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 8H13V3.5zM8.5 17.5v-5h1.25c.69 0 1.25.56 1.25 1.25v2.5c0 .69-.56 1.25-1.25 1.25H8.5zm1 -.75h.25c.28 0 .5-.22.5-.5v-2.5c0-.28-.22-.5-.5-.5H9.5v3.5zm3-4.25h2.25v.75H13.5v1h1.5v.75h-1.5v1.75h-.75v-4.25zm3.25 0h.75c.69 0 1.25.56 1.25 1.25v1.75c0 .69-.56 1.25-1.25 1.25h-.75v-4.25zm.75 3.5c.28 0 .5-.22.5-.5v-1.75c0-.28-.22-.5-.5-.5h0v2.75h0z" />
                                        </svg>
                                    @elseif($isWord)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 8H13V3.5zM7 17l1.5-5.5 1.5 4 1.5-4L13 17h-1l-1-3.2L10 17H9L8 13.8 7 17H7z" />
                                        </svg>
                                    @elseif($isExcel)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 8H13V3.5zM7 17l2.5-3.5L7 10h1.3l1.7 2.5L11.7 10H13l-2.5 3.5L13 17h-1.3l-1.7-2.6L8.3 17H7z" />
                                        </svg>
                                    @else
                                        <span class="uppercase text-[10px]">{{ $ext ?: 'file' }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color:var(--ink-1)">
                                        {{ $file->name ?? 'Document' }}</p>
                                    <p class="text-xs uppercase" style="color:var(--ink-3)">{{ $ext }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ file_path($file->id) }}" target="_blank" title="View"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium border transition hover:bg-black/5"
                                    style="border-color:var(--rule);color:var(--ink-2)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    View
                                </a>
                                <a href="{{ file_path($file->id) }}" download="{{ $file->name }}"
                                    title="Download"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium border transition hover:bg-black/5"
                                    style="border-color:var(--rule);color:var(--ink-2)">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path d="M12 4.5v9m0 0 3.5-3.5M12 13.5 8.5 10M4.5 19.5h15" />
                                    </svg>
                                    Download
                                </a>
                                @can('property.edit')
                                    <button x-show="editMode" wire:click="removeMedia('documents', {{ $file->id }})"
                                        title="Delete"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium border transition hover:bg-red-50"
                                        style="border-color:#fca5a5;color:#dc2626">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path d="M19 6l-1 14H6L5 6" />
                                            <path d="M10 11v6m4-6v6" />
                                            <path d="M9 6V4h6v2" />
                                        </svg>
                                        Delete
                                    </button>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
    @livewire('admin.file.media-picker', ['mediapickerModal' => false], key('media-picker-documents'))

    {{-- ─── DRAWER (unit add/edit) ───────────────────────────────────────────── --}}
    <div x-show="drawerOpen" x-transition.opacity style="display:none" class="fixed inset-0 z-50"
        style="background:rgba(20,18,16,.45);backdrop-filter:blur(4px)" @click.self="$wire.closeDrawer()">
    </div>

    <div class="fixed top-0 right-0 bottom-0 z-[99] bg-white bg flex flex-col shadow-2xl"
        style="width:440px;max-width:100vw;;transition:transform .25s cubic-bezier(.4,0,.2,1)"
        :style="drawerOpen ? 'transform:translateX(0)' : 'transform:translateX(100%)'">
        <div class="flex justify-between items-center px-5 py-4 border-b" style="border-color:var(--rule)">
            <h3 class="text-base font-semibold" style="color:var(--ink-1)">
                <span x-show="editMode">{{ $drawerUnitId ? 'Edit Unit' : 'Add Unit' }}</span>
                <span x-show="!editMode" style="display:none">Unit Details</span>
            </h3>
            <button wire:click="closeDrawer"
                class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-black/5"
                style="color:var(--ink-2)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-4">
            {{-- floor --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                    style="color:var(--ink-3)">Section</label>
                <select wire:model="dFloorId" :disabled="!editMode"
                    class="w-full rounded-lg border px-3 py-2 text-sm"
                    style="border-color:var(--rule);background:var(--paper)">
                    <option value="">— Select section —</option>
                    @foreach ($property->floors as $fl)
                        <option value="{{ $fl->id }}">{{ $fl->label ?? $fl->floor_name }}</option>
                    @endforeach
                </select>
                @error('dFloorId')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- code + type --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Unit Code *</label>
                    <input wire:model="dCode" :disabled="!editMode" type="text"
                        class="w-full rounded-lg border px-3 py-2 text-sm font-mono" style="border-color:var(--rule)"
                        placeholder="A-101">
                    @error('dCode')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider"
                            style="color:var(--ink-3)">Type</label>
                        @can('property.edit')
                            <button x-show="editMode" type="button" wire:click="openTypeModal()"
                                class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded border transition hover:opacity-80"
                                style="border-color:var(--rule);color:var(--ink-2);background:var(--canvas)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5"
                                    viewBox="0 0 24 24">
                                    <path d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Manage Types
                            </button>
                        @endcan
                    </div>
                    <select wire:model="dType" :disabled="!editMode"
                        class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)">
                        <option value="">— Select type —</option>
                        @foreach ($unitTypes as $ut)
                            <option value="{{ $ut->slug }}">{{ $ut->name }}</option>
                        @endforeach
                    </select>
                    @error('dType')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- purpose --}}
            <div x-data="{ purpose: $wire.entangle('dPurpose') }">
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                    style="color:var(--ink-3)">Purpose</label>
                <select wire:model.live="dPurpose" x-model="purpose" :disabled="!editMode"
                    class="w-full rounded-lg border px-3 py-2 text-sm"
                    style="border-color:var(--rule);background:var(--paper)">
                    <option value="">— Not specified —</option>
                    <option value="sell">Sell</option>
                    <option value="rent">Rent</option>
                </select>

                <div x-show="purpose === 'sell'" x-cloak class="mt-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Down Payment (%)</label>
                    <input wire:model="dDownPaymentPct" :disabled="!editMode" type="number" step="0.01"
                        min="0" max="100" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)" placeholder="e.g. 20">
                    @error('dDownPaymentPct')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="purpose === 'rent'" x-cloak class="mt-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Security Deposit (৳)</label>
                    <input wire:model="dDepositAmount" :disabled="!editMode" type="number" step="0.01"
                        min="0" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)" placeholder="e.g. 50000">
                    @error('dDepositAmount')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- status segmented --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                    style="color:var(--ink-3)">Status</label>
                <div class="seg-radio">
                    @foreach (['available' => 'Available', 'booked' => 'Booked', 'sold' => 'Sold', 'rented' => 'Rented'] as $val => $lbl)
                        <div>
                            <input type="radio" wire:model="dStatus" :disabled="!editMode"
                                value="{{ $val }}" id="ds_{{ $val }}">
                            <label for="ds_{{ $val }}"
                                class="{{ $val === 'available' ? 'av' : ($val === 'booked' ? 'bk' : ($val === 'sold' ? 'sd' : 'rt')) }}"
                                style="{{ $dStatus === $val ? ($val === 'available' ? 'background:var(--av-bg);border-color:var(--av-fg);color:var(--av-fg)' : ($val === 'booked' ? 'background:var(--bk-bg);border-color:var(--bk-fg);color:var(--bk-fg)' : ($val === 'sold' ? 'background:var(--sd-bg);border-color:var(--sd-fg);color:var(--sd-fg)' : 'background:var(--rt-bg);border-color:var(--rt-fg);color:var(--rt-fg)'))) : '' }}">
                                {{ $lbl }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- area + rate/sqft + price (price ⇄ rate auto-calc with area) --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Area (sft)</label>
                    <input wire:model.live.debounce.400ms="dArea" :disabled="!editMode" type="number"
                        step="0.01" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Rate / sft (৳)</label>
                    <input wire:model.live.debounce.400ms="dRatePerSqft" :disabled="!editMode" type="number"
                        step="0.001" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Price (৳)</label>
                    <input wire:model.live.debounce.400ms="dPrice" :disabled="!editMode" type="number"
                        step="0.001" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)" placeholder="0">
                </div>
            </div>
            <p class="text-xs" style="color:var(--ink-3); margin-top:-6px;">Set area, then enter rate/sft or price —
                the other fills automatically.</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Service Charge</label>
                    <input wire:model="dServiceCharge" :disabled="!editMode" type="number" step="0.001"
                        class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)"
                        placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Utility Charge</label>
                    <input wire:model="dUtilityCharge" :disabled="!editMode" type="number" step="0.001"
                        class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)"
                        placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Facing</label>
                    <input wire:model="dFacing" :disabled="!editMode" type="text"
                        class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)"
                        placeholder="North">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                    style="color:var(--ink-3)">Notes</label>
                <textarea wire:model="dNotes" :disabled="!editMode" rows="3"
                    class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)"></textarea>
            </div>

            {{-- media picker for unit --}}
            <div x-show="editMode" style="display:none">
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                    style="color:var(--ink-3)">Photos</label>
                <button type="button"
                    @click="$dispatch('openMediaPicker',{target:'unit_photo',multiple:true,type:'image'})"
                    class="w-full flex flex-col items-center justify-center py-6 rounded-lg border-2 border-dashed text-xs font-medium"
                    style="border-color:var(--ink-3);color:var(--ink-2)">
                    <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <polyline points="21 15 16 10 5 21" />
                    </svg>
                    Click to select from media library
                </button>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-5 py-4 border-t"
            style="border-color:var(--rule);background:rgba(0,0,0,.012)">
            <button wire:click="closeDrawer" class="px-4 py-2 rounded-lg text-sm border"
                style="border-color:var(--rule)">
                <span x-show="editMode">Cancel</span>
                <span x-show="!editMode" style="display:none">Close</span>
            </button>
            <button x-show="editMode" wire:click="saveUnit" class="px-4 py-2 rounded-lg text-sm font-semibold"
                style="background:var(--ink-1);color:var(--paper)">
                Save Unit
            </button>
        </div>
    </div>

    {{-- ─── UNIT TYPE MANAGEMENT MODAL ─────────────────────────────────────── --}}
    <div x-show="typeModalOpen" x-transition.opacity style="display:none"
        class="fixed inset-0 z-60 flex items-center justify-center bg-black/50 p-4">
        <div @click.stop class="w-full max-w-md rounded-xl shadow-2xl" style="background:var(--paper)" x-transition>
            <div class="flex justify-between items-center px-6 py-4 border-b" style="border-color:var(--rule)">
                <h2 class="text-base font-semibold" style="color:var(--ink-1)">Manage Unit Types</h2>
                <button wire:click="closeTypeModal" class="text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- existing types list --}}
            <div class="px-6 pt-4 pb-2 max-h-48 overflow-y-auto space-y-1.5">
                @forelse($unitTypes as $ut)
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg border"
                        style="border-color:var(--rule);background:var(--canvas)">
                        <div>
                            <span class="text-sm font-medium" style="color:var(--ink-1)">{{ $ut->name }}</span>
                            <span class="text-xs ml-2 font-mono"
                                style="color:var(--ink-3)">{{ $ut->slug }}</span>
                        </div>
                        @can('property.edit')
                            <div class="flex gap-1.5">
                                <button wire:click="openTypeModal({{ $ut->id }})"
                                    class="px-2 py-0.5 rounded text-xs border hover:opacity-80"
                                    style="border-color:var(--rule);color:var(--ink-2)">Edit</button>
                                <button wire:click="deleteType({{ $ut->id }})"
                                    class="px-2 py-0.5 rounded text-xs border hover:opacity-80"
                                    style="border-color:var(--rule);color:red">Del</button>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-xs text-center py-3" style="color:var(--ink-3)">No types yet.</p>
                @endforelse
            </div>

            {{-- add / edit form --}}
            @can('property.edit')
                <div class="px-6 pb-4 pt-3 border-t mt-3 space-y-3" style="border-color:var(--rule)">
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color:var(--ink-3)">
                        {{ $editingTypeId ? 'Edit Type' : 'Add New Type' }}
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color:var(--ink-3)">Name</label>
                            <input wire:model.live="tName" type="text"
                                class="w-full rounded-lg border px-3 py-2 text-sm"
                                style="border-color:var(--rule);background:var(--paper)" placeholder="e.g. Office">
                            @error('tName')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1" style="color:var(--ink-3)">Slug</label>
                            <input wire:model="tSlug" type="text"
                                class="w-full rounded-lg border px-3 py-2 text-sm font-mono"
                                style="border-color:var(--rule);background:var(--paper)" placeholder="auto-filled">
                            @error('tSlug')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        @if ($editingTypeId)
                            <button wire:click="openTypeModal()" class="px-3 py-1.5 rounded-md text-xs border"
                                style="border-color:var(--rule)">Cancel edit</button>
                        @endif
                        <button wire:click="saveType" class="px-4 py-1.5 rounded-md text-xs font-semibold"
                            style="background:var(--ink-1);color:var(--paper)">
                            {{ $editingTypeId ? 'Update' : 'Add Type' }}
                        </button>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    {{-- ─── FLOOR FORM MODAL ─────────────────────────────────────────────────── --}}
    <div x-show="floorFormOpen" x-transition.opacity style="display:none"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.stop class="w-full max-w-md rounded-xl shadow-2xl" style="background:var(--paper)" x-transition>
            <div class="flex justify-between items-center px-6 py-4 border-b" style="border-color:var(--rule)">
                <h2 class="text-base font-semibold">{{ $editFloorId ? 'Edit Section' : 'Add Section' }}</h2>
                <button wire:click="$set('floorFormOpen',false)" class="text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                            style="color:var(--ink-3)">Code</label>
                        <input wire:model.live="fCode" type="text"
                            class="w-full rounded-lg border px-3 py-2 text-sm font-mono"
                            style="border-color:var(--rule)" placeholder="G / 1 / 2 / T">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                            style="color:var(--ink-3)">Label *</label>
                        <input wire:model="fLabel" type="text" class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color:var(--rule)" placeholder="Ground / Section 1">
                        @error('fLabel')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Section Area (sft)</label>
                    <input wire:model="fFloorArea" type="number" step="0.01"
                        class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:var(--rule)">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style="color:var(--ink-3)">Remarks</label>
                    <textarea wire:model="fRemarks" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color:var(--rule)"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t"
                style="border-color:var(--rule);background:rgba(0,0,0,.012)">
                <button wire:click="$set('floorFormOpen',false)" class="px-4 py-2 rounded-md text-sm border"
                    style="border-color:var(--rule)">Cancel</button>
                <button wire:click="saveFloor" class="px-4 py-2 rounded-md text-sm font-semibold"
                    style="background:var(--ink-1);color:var(--paper)">Save Section</button>
            </div>
        </div>
    </div>

</div>
