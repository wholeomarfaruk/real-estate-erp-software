<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Projects\EstimateStatus;
use App\Models\Project;
use App\Models\StockConsumption;
use Livewire\Component;

class ProjectConsumption extends Component
{
    public Project $project;
    public string $filterPhase  = '';
    public string $filterStatus = '';

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403);
        }
        $this->project = $project;
    }

    public function render()
    {
        // Approved estimate material items, grouped by work_phase + product
        $approvedEstimate = $this->project->estimates()
            ->where('status', EstimateStatus::APPROVED->value)
            ->with('items.material')
            ->latest('version')
            ->first();

        $estimateByMaterial = collect();
        if ($approvedEstimate) {
            $estimateByMaterial = $approvedEstimate->items
                ->where('cost_type', 'material')
                ->keyBy('material_id');
        }

        // Actual consumption from inventory (posted consumptions only)
        $consumptions = StockConsumption::with(['items.product'])
            ->where('project_id', $this->project->id)
            ->where('status', 'posted')
            ->get();

        // Aggregate consumed qty per material + cost info
        $consumedByMaterial = [];
        $consumptionCostByMaterial = [];  // Store unit price from actual consumption
        foreach ($consumptions as $con) {
            foreach ($con->items as $item) {
                $mid = $item->product_id;
                $consumedByMaterial[$mid] = ($consumedByMaterial[$mid] ?? 0) + (float)$item->quantity;
                
                // Store consumption unit price (use last known price or average)
                if ((float)$item->unit_price > 0) {
                    $consumptionCostByMaterial[$mid] = (float)$item->unit_price;
                }
            }
        }

        // Build rows: merge estimate items + consumed
        $allMaterialIds = array_unique(array_merge(
            $estimateByMaterial->keys()->toArray(),
            array_keys($consumedByMaterial)
        ));

        $rows = [];
        $totalEstimatedValue = 0;
        $totalConsumedValue = 0;
        $totalRemainingValue = 0;
        $totalOverValue = 0;

        foreach ($allMaterialIds as $mid) {
            $estItem = $estimateByMaterial->get($mid);
            $estQty  = $estItem ? (float)$estItem->estimated_qty : 0;
            $estValue = $estItem ? (float)$estItem->estimated_amount : 0;
            $estRate = $estItem ? (float)$estItem->estimated_rate : 0;
            $consumed = $consumedByMaterial[$mid] ?? 0;
            $remaining = $estQty - $consumed;
            $extra     = $consumed > $estQty ? $consumed - $estQty : 0;
            $pct       = $estQty > 0 ? min(100, round(($consumed / $estQty) * 100, 1)) : 0;

            // Calculate consumed and remaining value based on proportional consumption
            $consumedValue = $estQty > 0 ? ($consumed / $estQty) * $estValue : 0;
            $remainingValue = max(0, $estValue - $consumedValue);
            
            // Over-consumption value: use estimated rate if available, otherwise consumption unit price
            $extraValue = 0;
            if ($extra > 0) {
                if ($estRate > 0) {
                    // Prefer estimated rate
                    $extraValue = $extra * $estRate;
                } elseif (!empty($consumptionCostByMaterial[$mid]) && $consumptionCostByMaterial[$mid] > 0) {
                    // Fall back to actual consumption unit price
                    $extraValue = $extra * $consumptionCostByMaterial[$mid];
                } elseif ($estQty > 0 && $estValue > 0) {
                    // Final fallback to proportional if no rate available
                    $extraValue = ($extra / $estQty) * $estValue;
                }
            }

            $status = 'not_started';
            if ($consumed > 0 && $consumed < $estQty) $status = 'in_progress';
            elseif ($estQty > 0 && $consumed >= $estQty && $extra === 0) $status = 'completed';
            elseif ($extra > 0) $status = 'over_consumed';

            $product = $estItem?->material ?? optional(
                \App\Models\Product::find($mid)
            );

            $row = [
                'material_id' => $mid,
                'name'        => $product?->name ?? 'Unknown Material',
                'unit'        => $estItem?->unit ?? $product?->unit ?? '—',
                'work_phase'  => $estItem?->work_phase?->value ?? '',
                'est_qty'     => $estQty,
                'est_value'   => $estValue,
                'consumed'    => $consumed,
                'consumed_value' => $consumedValue,
                'remaining'   => max(0, $remaining),
                'remaining_value' => $remainingValue,
                'extra'       => $extra,
                'extra_value' => $extraValue,
                'pct'         => $pct,
                'status'      => $status,
            ];

            // Accumulate totals
            $totalEstimatedValue += $estValue;
            $totalConsumedValue += $consumedValue;
            $totalRemainingValue += $remainingValue;
            $totalOverValue += $extraValue;

            if ($this->filterPhase && $row['work_phase'] !== $this->filterPhase) continue;
            if ($this->filterStatus && $row['status'] !== $this->filterStatus) continue;

            $rows[] = $row;
        }

        // Sort by work_phase, then name
        usort($rows, fn($a, $b) => ($a['work_phase'] <=> $b['work_phase']) ?: ($a['name'] <=> $b['name']));
        $rowsByPhase = collect($rows)->groupBy('work_phase');

        $totalEstimated = array_sum(array_column($rows, 'est_qty'));
        $totalConsumed  = array_sum(array_column($rows, 'consumed'));
        $totalOver      = array_sum(array_column($rows, 'extra'));
        $overCount      = count(array_filter($rows, fn($r) => $r['status'] === 'over_consumed'));

        $project = $this->project;
        return view('livewire.admin.projects.project-consumption', compact(
            'project', 'rowsByPhase', 'totalEstimated', 'totalConsumed', 'totalOver', 'overCount',
            'totalEstimatedValue', 'totalConsumedValue', 'totalRemainingValue', 'totalOverValue'
        ))->layout('layouts.admin.admin');
    }
}
