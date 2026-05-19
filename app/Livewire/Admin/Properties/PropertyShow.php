<?php

namespace App\Livewire\Admin\Properties;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Employee;
use App\Models\File;
use App\Models\Property;
use App\Models\PropertyFloor;
use App\Models\PropertyUnit;
use App\Models\UnitType;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PropertyShow extends Component
{
    use WithMediaPicker;

    public Property $property;
    public array $documents = [];
    public array $propertyImages = [];

    // ── unit drawer ───────────────────────────────────────────────────────────
    public bool $drawerOpen  = false;
    public ?int $drawerUnitId = null;  // null = creating new

    public ?int   $dFloorId       = null;
    public string $dCode          = '';
    public string $dType          = 'flat';
    public string $dStatus        = 'available';
    public string $dArea          = '';
    public string $dPrice         = '';
    public string $dServiceCharge      = '';
    public string $dFacing             = '';
    public string $dNotes              = '';
    public string $dPurpose            = '';
    public string $dDownPaymentPct     = '';
    public string $dDepositAmount      = '';

    // ── unit type modal ───────────────────────────────────────────────────────
    public bool   $typeModalOpen  = false;
    public ?int   $editingTypeId  = null;
    public string $tName          = '';
    public string $tSlug          = '';

    // ── floor form ────────────────────────────────────────────────────────────
    public bool   $floorFormOpen  = false;
    public ?int   $editFloorId    = null;
    public string $fCode          = '';
    public string $fLabel         = '';
    public string $fFloorArea     = '';
    public string $fRemarks       = '';

    public function mount(Property $property): void
    {
        abort_unless(auth()->user()?->can('property.view'), 403);
        $this->property = $property;
        $this->loadProperty();
        $this->documents       = $this->property->documents ?? [];
        $this->propertyImages  = $this->property->property_images ?? [];
    }

    // ── Reload ────────────────────────────────────────────────────────────────

    private function loadProperty(): void
    {
        $this->property = $this->property->fresh()->load([
            'engineer:id,name',
            'floors.units',
        ]);
        $this->documents      = $this->property->documents ?? [];
        $this->propertyImages = $this->property->property_images ?? [];
    }

    // ── Floor management ──────────────────────────────────────────────────────

    public function openFloorForm(?int $floorId = null): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $this->floorFormOpen = true;
        $this->editFloorId   = $floorId;

        if ($floorId) {
            $floor          = PropertyFloor::findOrFail($floorId);
            $this->fCode    = $floor->code    ?? '';
            $this->fLabel   = $floor->label   ?? '';
            $this->fFloorArea = $floor->floor_area ?? '';
            $this->fRemarks = $floor->remarks  ?? '';
        } else {
            $this->fCode = $this->fLabel = $this->fFloorArea = $this->fRemarks = '';
        }
    }

    public function saveFloor(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);

        $this->validate([
            'fLabel' => 'required|string|max:100',
            'fCode'  => 'nullable|string|max:10',
        ]);

        $data = [
            'property_id' => $this->property->id,
            'label'       => $this->fLabel,
            'code'        => $this->fCode !== '' ? $this->fCode : null,
            'floor_area'  => $this->fFloorArea !== '' ? $this->fFloorArea : null,
            'remarks'     => $this->fRemarks ?: null,
            'sort_order'  => $this->editFloorId
                ? PropertyFloor::find($this->editFloorId)?->sort_order ?? 0
                : ($this->property->floors->max('sort_order') ?? 0) + 1,
        ];

        if ($this->editFloorId) {
            PropertyFloor::findOrFail($this->editFloorId)->update($data);
        } else {
            PropertyFloor::create($data);
        }

        $this->floorFormOpen = false;
        $this->loadProperty();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor saved.']);
    }

    public function deleteFloor(int $floorId): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $floor = PropertyFloor::findOrFail($floorId);

        if ($floor->units()->count() > 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Remove all units from this floor first.']);
            return;
        }

        $floor->delete();
        $this->loadProperty();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Floor deleted.']);
    }

    // ── Unit type management ─────────────────────────────────────────────────

    public function openTypeModal(?int $id = null): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $this->editingTypeId = $id;
        if ($id) {
            $type        = UnitType::findOrFail($id);
            $this->tName = $type->name;
            $this->tSlug = $type->slug;
        } else {
            $this->tName = $this->tSlug = '';
        }
        $this->typeModalOpen = true;
    }

    public function updatedTName(string $value): void
    {
        if (! $this->editingTypeId) {
            $this->tSlug = UnitType::makeSlug($value);
        }
    }

    public function saveType(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);

        $slugRule = $this->editingTypeId
            ? 'required|string|max:60|unique:unit_types,slug,' . $this->editingTypeId
            : 'required|string|max:60|unique:unit_types,slug';

        $this->validate([
            'tName' => 'required|string|max:100',
            'tSlug' => $slugRule,
        ]);

        if ($this->editingTypeId) {
            UnitType::findOrFail($this->editingTypeId)->update(['name' => $this->tName, 'slug' => $this->tSlug]);
            $msg = 'Unit type updated.';
        } else {
            UnitType::create(['name' => $this->tName, 'slug' => $this->tSlug]);
            $msg = 'Unit type added.';
        }

        $this->typeModalOpen = false;
        $this->tName = $this->tSlug = '';
        $this->editingTypeId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => $msg]);
    }

    public function deleteType(int $id): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);

        if (PropertyUnit::where('type', UnitType::findOrFail($id)->slug)->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete: units are using this type.']);
            return;
        }

        UnitType::destroy($id);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit type deleted.']);
    }

    public function closeTypeModal(): void
    {
        $this->typeModalOpen = false;
        $this->tName = $this->tSlug = '';
        $this->editingTypeId = null;
    }

    // ── Unit drawer ───────────────────────────────────────────────────────────

    public function openUnitAdd(int $floorId): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $this->resetDrawer();
        $this->dFloorId   = $floorId;
        $this->drawerOpen = true;
    }

    public function openUnitEdit(int $unitId): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $unit = PropertyUnit::findOrFail($unitId);
        $this->drawerUnitId    = $unitId;
        $this->dFloorId        = $unit->property_floor_id;
        $this->dCode           = $unit->effective_code;
        $this->dType           = $unit->effective_type;
        $this->dStatus         = $unit->effective_status;
        $this->dArea           = $unit->effective_area ?: '';
        $this->dPrice          = $unit->effective_price ?: '';
        $this->dServiceCharge  = $unit->service_charge ?? '';
        $this->dFacing         = $unit->facing ?? '';
        $this->dNotes          = $unit->notes ?? '';
        $this->dPurpose        = $unit->purpose ?? '';
        $this->dDownPaymentPct = $unit->down_payment_percentage ? (string) $unit->down_payment_percentage : '';
        $this->dDepositAmount  = $unit->deposit_amount ? (string) $unit->deposit_amount : '';
        $this->drawerOpen      = true;
    }

    public function saveUnit(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);

        $this->validate([
            'dCode'          => 'required|string|max:30',
            'dType'          => 'required|exists:unit_types,slug',
            'dStatus'        => 'required|in:available,booked,sold,rented',
            'dFloorId'       => 'required|integer|exists:property_floors,id',
            'dArea'          => 'nullable|numeric|min:0',
            'dPrice'         => 'nullable|numeric|min:0',
            'dPurpose'       => 'nullable|in:sell,rent',
            'dDownPaymentPct'=> 'nullable|numeric|min:0|max:100',
            'dDepositAmount' => 'nullable|numeric|min:0',
        ]);

        $data = [
            'property_id'       => $this->property->id,
            'property_floor_id' => $this->dFloorId,
            'code'              => $this->dCode,
            'type'              => $this->dType,
            'status'            => $this->dStatus,
            'area'              => $this->dArea !== '' ? $this->dArea : null,
            'price'             => $this->dPrice !== '' ? $this->dPrice : 0,
            'service_charge'    => $this->dServiceCharge !== '' ? $this->dServiceCharge : 0,
            'facing'                   => $this->dFacing ?: null,
            'notes'                    => $this->dNotes ?: null,
            'purpose'                  => $this->dPurpose ?: null,
            'down_payment_percentage'  => ($this->dPurpose === 'sell' && $this->dDownPaymentPct !== '') ? $this->dDownPaymentPct : null,
            'deposit_amount'           => ($this->dPurpose === 'rent' && $this->dDepositAmount !== '') ? $this->dDepositAmount : null,
        ];

        if ($this->drawerUnitId) {
            PropertyUnit::findOrFail($this->drawerUnitId)->update($data);
            $msg = 'Unit updated.';
        } else {
            $data['sort_order'] = PropertyUnit::where('property_floor_id', $this->dFloorId)->max('sort_order') + 1;
            PropertyUnit::create($data);
            $msg = 'Unit added.';
        }

        $this->drawerOpen = false;
        $this->loadProperty();
        $this->dispatch('toast', ['type' => 'success', 'message' => $msg]);
    }

    public function deleteUnit(int $unitId): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        PropertyUnit::findOrFail($unitId)->delete();
        $this->loadProperty();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Unit deleted.']);
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->resetDrawer();
    }

    // ── Drag-and-drop reorder (called by fetch from JS) are handled via API ──
    // The Livewire component exposes a reloadBuilding method for JS to call after
    // a successful fetch reorder.
    public function reloadBuilding(): void
    {
        $this->loadProperty();
    }

    public function reloadBuildingWithToast(string $message): void
    {
        $this->loadProperty();
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    // ── Documents (images + files) via WithMediaPicker + documents JSON column ─
    public function saveDocuments(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $this->property->documents = $this->documents;
        $this->property->save();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Documents saved.']);
    }

    public function savePropertyImages(): void
    {
        abort_unless(auth()->user()?->can('property.edit'), 403);
        $this->property->property_images = $this->propertyImages ?: null;
        $this->property->save();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Slider images saved.']);
    }

    // ── render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        $floors = $this->property->floors->map(function (PropertyFloor $floor): array {
            $units = $floor->units->map(fn (PropertyUnit $u): array => [
                'id'     => $u->id,
                'code'   => $u->effective_code,
                'type'   => $u->effective_type,
                'status' => $u->effective_status,
                'price'  => $u->effective_price,
                'area'   => $u->effective_area,
            ])->values()->toArray();

            $counts = [
                'available' => collect($units)->where('status', 'available')->count(),
                'booked'    => collect($units)->where('status', 'booked')->count(),
                'sold'      => collect($units)->where('status', 'sold')->count(),
                'rented'    => collect($units)->where('status', 'rented')->count(),
            ];

            return [
                'id'         => $floor->id,
                'code'       => $floor->code,
                'label'      => $floor->label ?? $floor->floor_name ?? 'Floor',
                'sort_order' => $floor->sort_order ?? 0,
                'floor_area' => $floor->floor_area,
                'units'      => $units,
                'counts'     => $counts,
            ];
        })->values()->toArray();

        $allUnits = $this->property->units;
        $kpi = [
            'total'     => $allUnits->count(),
            'available' => $allUnits->where('effective_status', 'available')->count(),
            'booked'    => $allUnits->where('effective_status', 'booked')->count(),
            'sold'      => $allUnits->where('effective_status', 'sold')->count(),
            'rented'    => $allUnits->where('effective_status', 'rented')->count(),
            'floors'    => $this->property->floors->count(),
            'total_area'=> (float) $this->property->total_area,
        ];

        $employees     = Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $unitTypes     = UnitType::orderBy('name')->get();
        $documentFiles     = $this->documents
            ? File::whereIn('id', $this->documents)->get()
            : collect();

        $propertyImageFiles = $this->propertyImages
            ? File::whereIn('id', $this->propertyImages)->get()->sortBy(fn($f) => array_search($f->id, $this->propertyImages))->values()
            : collect();

        return view('livewire.admin.properties.property-show', [
            'property'           => $this->property,
            'floors'             => $floors,
            'kpi'                => $kpi,
            'employees'          => $employees,
            'unitTypes'          => $unitTypes,
            'documentFiles'      => $documentFiles,
            'propertyImageFiles' => $propertyImageFiles,
            'canEdit'            => auth()->user()?->can('property.edit'),
        ])->layout('layouts.admin.admin');
    }

    private function resetDrawer(): void
    {
        $this->drawerUnitId = null;
        $this->dFloorId = null;
        $this->dCode = $this->dType = '';
        $this->dStatus = 'available';
        $this->dArea = $this->dPrice = $this->dServiceCharge = $this->dFacing = $this->dNotes = '';
        $this->dPurpose = $this->dDownPaymentPct = $this->dDepositAmount = '';
    }
}
