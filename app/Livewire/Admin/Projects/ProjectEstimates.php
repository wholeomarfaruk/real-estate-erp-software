<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Projects\CostType;
use App\Enums\Projects\EstimateStatus;
use App\Enums\Projects\WorkPhase;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\EstimateItem;
use App\Models\File;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectEstimate;
use App\Models\TransactionCategory;
use Livewire\Component;

class ProjectEstimates extends Component
{
    use WithMediaPicker;

    public Project $project;

    public string $filterPhase    = '';
    public string $filterCostType = '';
    public ?int $activeEstimateId = null;

    // ── Builder (create / edit) state ────────────────────
    public bool $showForm   = false;
    public ?int $editingId  = null;
    public bool $isEditingLocked = false;

    public string $form_title         = '';
    public string $form_estimate_date = '';
    public string $form_notes         = '';
    public array $attachments        = [];

    /**
     * Each row:
     * id, cost_type, material_id, transaction_category_id, name,
     * unit, estimated_qty, estimated_rate, work_phase, is_optional, remarks
     */
    public array $items = [];

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403);
        }
        $this->project = $project;
    }

    public function selectEstimate(int $id): void
    {
        $this->activeEstimateId = $id;
        $this->filterPhase      = '';
        $this->filterCostType   = '';
    }

    // ── Create / Edit builder ────────────────────────────

    public function createEstimate(): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }

        $this->editingId          = null;
        $this->isEditingLocked    = false;
        $this->form_title         = 'Project Estimate';
        $this->form_estimate_date = now()->format('Y-m-d');
        $this->form_notes         = '';
        $this->attachments        = [];
        $this->items              = [$this->blankItem()];
        $this->resetValidation();
        $this->showForm           = true;
    }

    public function editEstimate(int $id): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }

        $estimate = ProjectEstimate::with('items')
            ->where('project_id', $this->project->id)
            ->findOrFail($id);

        if ($estimate->isLocked()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Approved estimates are locked. Duplicate it to make changes.']);
            return;
        }

        $this->editingId          = $estimate->id;
        $this->isEditingLocked    = false;
        $this->form_title         = $estimate->title ?? 'Project Estimate';
        $this->form_estimate_date = optional($estimate->estimate_date)->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->form_notes         = $estimate->notes ?? '';
        $this->attachments        = $estimate->attachments ?? [];

        $this->items = $estimate->items->map(fn($it) => [
            'id'                      => $it->id,
            'cost_type'               => $it->cost_type?->value ?? 'material',
            'material_id'             => $it->material_id,
            'transaction_category_id' => $it->transaction_category_id,
            'name'                    => $it->name ?? '',
            'unit'                    => $it->unit ?? '',
            'estimated_qty'           => (float) $it->estimated_qty,
            'estimated_rate'          => (float) $it->estimated_rate,
            'work_phase'              => $it->work_phase?->value ?? '',
            'is_optional'             => (bool) $it->is_optional,
            'remarks'                 => $it->remarks ?? '',
        ])->toArray();

        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }

        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm  = false;
        $this->editingId = null;
        $this->isEditingLocked = false;
        $this->items     = [];
        $this->attachments = [];
        $this->resetValidation();
    }

    public function mediaSelected($field, $id): void
    {
        // Prevent adding attachments to locked estimates
        if ($this->editingId && $field === 'attachments') {
            $estimate = ProjectEstimate::find($this->editingId);
            if ($estimate && $estimate->isLocked()) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot modify attachments on locked estimates. Duplicate the estimate to make changes.']);
                return;
            }
        }
        parent::mediaSelected($field, $id);
    }

    public function removeMedia($field, $id = null): void
    {
        // Prevent removing attachments from locked estimates
        if ($this->editingId && $field === 'attachments') {
            $estimate = ProjectEstimate::find($this->editingId);
            if ($estimate && $estimate->isLocked()) {
                $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot modify attachments on locked estimates. Duplicate the estimate to make changes.']);
                return;
            }
        }
        parent::removeMedia($field, $id);
    }

    protected function blankItem(): array
    {
        return [
            'id'                      => null,
            'cost_type'               => 'material',
            'material_id'             => null,
            'transaction_category_id' => null,
            'name'                    => '',
            'unit'                    => '',
            'estimated_qty'           => 1,
            'estimated_rate'          => 0,
            'work_phase'              => '',
            'is_optional'             => false,
            'remarks'                 => '',
        ];
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        if (empty($this->items)) {
            $this->items = [$this->blankItem()];
        }
    }

    /** When a material is picked, auto-fill the unit from the product. */
    public function updatedItems($value, $key): void
    {
        // $key looks like "0.material_id" or "2.cost_type"
        if (str_ends_with($key, '.material_id') && $value) {
            $index = (int) explode('.', $key)[0];
            $product = Product::with('unit')->find($value);
            if ($product) {
                $this->items[$index]['name'] = $product->name;
                $this->items[$index]['unit'] = $product->unit?->name ?? $product->unit ?? '';
            }
        }

        // Reset the opposite reference when cost_type changes
        if (str_ends_with($key, '.cost_type')) {
            $index = (int) explode('.', $key)[0];
            if ($value === 'material') {
                $this->items[$index]['transaction_category_id'] = null;
            } else {
                $this->items[$index]['material_id'] = null;
            }
        }

        // When an expense category is picked, fill the name
        if (str_ends_with($key, '.transaction_category_id') && $value) {
            $index = (int) explode('.', $key)[0];
            $cat = TransactionCategory::find($value);
            if ($cat) {
                $this->items[$index]['name'] = $cat->name;
            }
        }
    }

    protected function rules(): array
    {
        return [
            'form_title'         => 'required|string|max:255',
            'form_estimate_date' => 'required|date',
            'form_notes'         => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.cost_type'  => 'required|in:material,labour,overhead,indirect',
            'items.*.material_id'             => 'nullable|exists:products,id',
            'items.*.transaction_category_id' => 'nullable|exists:transaction_categories,id',
            'items.*.name'           => 'required|string|max:255',
            'items.*.unit'           => 'required|string|max:50',
            'items.*.estimated_qty'  => 'required|numeric|min:0',
            'items.*.estimated_rate' => 'required|numeric|min:0',
            'items.*.work_phase'     => 'nullable|in:foundation,structure,brick_work,plaster,electrical,plumbing,finishing,other',
            'items.*.is_optional'    => 'boolean',
            'items.*.remarks'        => 'nullable|string|max:500',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required'                  => 'Add at least one line item.',
            'items.min'                       => 'Add at least one line item.',
            'items.*.name.required'           => 'Item name is required.',
            'items.*.unit.required'           => 'Unit is required (e.g. Bag, Day, LS).',
            'items.*.estimated_qty.required'  => 'Quantity is required.',
            'items.*.estimated_rate.required' => 'Rate is required.',
        ];
    }

    public function saveEstimate(string $status = 'draft'): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }

        // Normalise each row: empty selects come through as '' not null, and
        // only the relevant reference (material vs category) should be kept.
        foreach ($this->items as $i => $row) {
            $isMaterial = ($row['cost_type'] ?? 'material') === 'material';
            $this->items[$i]['material_id'] = $isMaterial
                ? ($row['material_id'] ?: null)
                : null;
            $this->items[$i]['transaction_category_id'] = !$isMaterial
                ? ($row['transaction_category_id'] ?: null)
                : null;
        }

        // ── Validation (toast on failure) ──
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first();
            $this->dispatch('toast', ['type' => 'error', 'message' => $first ?: 'Please fix the highlighted fields.']);
            throw $e; // keep the error bag so inline errors still render
        }

        // Per-row check: material-type items must have a material selected.
        $rowErrors = false;
        foreach ($this->items as $i => $row) {
            if (($row['cost_type'] ?? null) === 'material' && empty($row['material_id'])) {
                $this->addError("items.{$i}.material_id", 'Select a material for material-type items.');
                $rowErrors = true;
            }
        }
        if ($rowErrors) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Select a material for every material-type item.']);
            return;
        }

        try {
            $total = collect($this->items)
                ->sum(fn($it) => (float) $it['estimated_qty'] * (float) $it['estimated_rate']);

            if ($this->editingId) {
                $estimate = ProjectEstimate::where('project_id', $this->project->id)->findOrFail($this->editingId);
                if ($estimate->isLocked()) {
                    abort(403, 'Locked estimate cannot be edited.');
                }
                $estimate->update([
                    'title'                  => $this->form_title,
                    'estimate_date'          => $this->form_estimate_date,
                    'notes'                  => $this->form_notes,
                    'status'                 => $status,
                    'total_estimated_amount' => $total,
                    'attachments'            => !empty($this->attachments) ? $this->attachments : null,
                ]);
                $estimate->items()->delete();
            } else {
                $maxVersion = ProjectEstimate::where('project_id', $this->project->id)->max('version') ?? 0;
                $version    = $maxVersion + 1;

                $estimate = ProjectEstimate::create([
                    'project_id'             => $this->project->id,
                    'estimate_no'            => $this->generateEstimateNo($version),
                    'title'                  => $this->form_title,
                    'version'                => $version,
                    'estimate_date'          => $this->form_estimate_date,
                    'status'                 => $status,
                    'total_estimated_amount' => $total,
                    'notes'                  => $this->form_notes,
                    'attachments'            => !empty($this->attachments) ? $this->attachments : null,
                    'created_by'             => auth()->id(),
                ]);
            }

            foreach ($this->items as $sort => $row) {
                $isMaterial = $row['cost_type'] === 'material';
                EstimateItem::create([
                    'project_estimate_id'     => $estimate->id,
                    'material_id'             => $isMaterial ? $row['material_id'] : null,
                    'transaction_category_id' => !$isMaterial ? ($row['transaction_category_id'] ?: null) : null,
                    'name'                    => $row['name'],
                    'unit'                    => $row['unit'],
                    'estimated_qty'           => $row['estimated_qty'],
                    'estimated_rate'          => $row['estimated_rate'],
                    'cost_type'               => $row['cost_type'],
                    'work_phase'              => $row['work_phase'] ?: null,
                    'sort_order'              => $sort,
                    'is_optional'             => (bool) $row['is_optional'],
                    'remarks'                 => $row['remarks'] ?: null,
                ]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Save failed: ' . $e->getMessage()]);
            return;
        }

        $this->activeEstimateId = $estimate->id;
        $this->showForm         = false;
        $this->editingId        = null;
        $this->items            = [];

        $label = $status === 'submitted' ? 'submitted for approval' : 'saved as draft';
        $this->dispatch('toast', ['type' => 'success', 'message' => "Estimate {$label}."]);
    }

    protected function generateEstimateNo(int $version): string
    {
        return 'EST-' . str_pad((string) $this->project->id, 4, '0', STR_PAD_LEFT)
            . '-V' . $version;
    }

    public function approveEstimate(int $id): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }
        $estimate = ProjectEstimate::where('project_id', $this->project->id)->findOrFail($id);
        $estimate->update([
            'status'      => EstimateStatus::APPROVED->value,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Estimate approved and locked.']);
    }

    public function submitEstimate(int $id): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }
        $estimate = ProjectEstimate::where('project_id', $this->project->id)->findOrFail($id);
        $estimate->update(['status' => EstimateStatus::SUBMITTED->value]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Estimate submitted for approval.']);
    }

    public function duplicateEstimate(int $id): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }
        $source = ProjectEstimate::with('items')->where('project_id', $this->project->id)->findOrFail($id);
        $maxVersion = ProjectEstimate::where('project_id', $this->project->id)->max('version') ?? 0;
        $version = $maxVersion + 1;

        $new = $source->replicate();
        $new->version     = $version;
        $new->status      = EstimateStatus::DRAFT->value;
        $new->estimate_no = $this->generateEstimateNo($version);
        $new->created_by  = auth()->id();
        $new->approved_by = null;
        $new->approved_at = null;
        $new->attachments = $source->attachments;
        $new->save();

        foreach ($source->items as $item) {
            $clone = $item->replicate();
            $clone->project_estimate_id = $new->id;
            $clone->save();
        }

        $this->activeEstimateId = $new->id;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Estimate duplicated as V' . $new->version . '.']);
    }

    public function deleteEstimate(int $id): void
    {
        if (!auth()->user()->can('project.edit')) {
            abort(403);
        }
        $estimate = ProjectEstimate::where('project_id', $this->project->id)->findOrFail($id);
        if ($estimate->isLocked()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Approved estimates cannot be deleted.']);
            return;
        }
        $estimate->items()->delete();
        $estimate->delete();
        $this->activeEstimateId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Estimate deleted.']);
    }

    /** Live grand total while building. */
    public function getFormTotalProperty(): float
    {
        return collect($this->items)
            ->sum(fn($it) => (float) ($it['estimated_qty'] ?? 0) * (float) ($it['estimated_rate'] ?? 0));
    }

    public function render()
    {
        $estimates = ProjectEstimate::with(['items.transactionCategory', 'items.material', 'createdBy', 'approvedBy'])
            ->where('project_id', $this->project->id)
            ->orderBy('version')
            ->get();

        $activeEstimate = $this->activeEstimateId
            ? $estimates->firstWhere('id', $this->activeEstimateId)
            : $estimates->last();

        $boqItems = collect();
        $totals   = ['material' => 0, 'labour' => 0, 'overhead' => 0, 'indirect' => 0, 'grand' => 0];
        $approvedBudget = 0;

        if ($activeEstimate) {
            $items = $activeEstimate->items;
            if ($this->filterPhase)    $items = $items->where('work_phase', $this->filterPhase);
            if ($this->filterCostType) $items = $items->where('cost_type', $this->filterCostType);
            $boqItems = $items->sortBy('sort_order')->groupBy('work_phase');

            foreach ($activeEstimate->items as $item) {
                $type = $item->cost_type?->value ?? 'indirect';
                $totals[$type] = ($totals[$type] ?? 0) + (float) $item->estimated_amount;
                $totals['grand'] += (float) $item->estimated_amount;
            }
        }

        $approved = ProjectEstimate::where('project_id', $this->project->id)
            ->where('status', EstimateStatus::APPROVED->value)
            ->latest('version')->first();
        if ($approved) {
            $approvedBudget = (float) $approved->items->sum('estimated_amount');
        }

        $totalSpent = $this->project->totalSpent();

        // Builder dropdowns
        $materials  = Product::with('unit')->orderBy('name')->get(['id', 'name', 'product_unit_id', 'unit']);
        $categories = TransactionCategory::where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')->get(['id', 'name']);

        $project = $this->project;

        $showEditButton = false;
        return view('livewire.admin.projects.project-estimates', compact(
            'project', 'estimates', 'activeEstimate', 'boqItems', 'totals',
            'approvedBudget', 'totalSpent', 'materials', 'categories', 'showEditButton'
        ))->layout('layouts.admin.admin');
    }
}
