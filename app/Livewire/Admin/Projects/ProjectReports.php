<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Projects\EstimateStatus;
use App\Models\Project;
use App\Models\StockConsumption;
use Livewire\Component;

class ProjectReports extends Component
{
    public Project $project;

    public function mount(Project $project)
    {
        if (!auth()->user()->can('project.view')) {
            abort(403);
        }
        $this->project = $project;
    }

    public function render()
    {
        // Approved estimate totals
        $approved = $this->project->estimates()
            ->where('status', EstimateStatus::APPROVED->value)
            ->with('items')
            ->latest('version')
            ->first();

        $estMaterial = 0;
        $estLabour   = 0;
        $estOther    = 0;
        $estByPhase  = [];

        if ($approved) {
            foreach ($approved->items as $item) {
                $phase = $item->work_phase?->value ?? 'other';
                $type  = $item->cost_type?->value ?? 'indirect';
                $amt   = (float)$item->estimated_amount;
                $estByPhase[$phase][$type] = ($estByPhase[$phase][$type] ?? 0) + $amt;

                match($type) {
                    'material' => $estMaterial += $amt,
                    'labour'   => $estLabour   += $amt,
                    default    => $estOther     += $amt,
                };
            }
        }
        $approvedBudget = $estMaterial + $estLabour + $estOther;

        // Actual: material from stock consumptions (with phase info from transaction)
        $consumptions = StockConsumption::with('items.product', 'transaction')
            ->where('project_id', $this->project->id)
            ->where('status', 'posted')
            ->get();

        $actualMaterialCost = 0;
        $materialByPhase = []; // phase => total cost

        foreach ($consumptions as $con) {
            $phase = null;
            if ($con->transaction) {
                $phase = $con->transaction->external_data['project_work_phase'] ?? null;
            }
            $phase = $phase && in_array($phase, array_map(fn($c) => $c->value, \App\Enums\Projects\WorkPhase::cases())) ? $phase : 'others';

            if (!isset($materialByPhase[$phase])) {
                $materialByPhase[$phase] = 0;
            }

            $itemCost = 0;
            foreach ($con->items as $item) {
                $itemCost += (float)($item->total_price ?? ($item->quantity * ($item->unit_price ?? 0)));
            }

            $actualMaterialCost += $itemCost;
            $materialByPhase[$phase] += $itemCost;
        }

        // Actual: labour + other from posted expense transactions (ledger)
        $expenses = $this->project->expenseTransactions()
            ->with('lines')
            ->get();

        $actualOther  = 0;
        $actualByPhase = []; // phase => ['other' => Y]

        foreach ($expenses as $exp) {
            // All expense transactions fall under "other" (categories removed)
            $amount = (float) $exp->lines->sum('credit');
            $actualOther += $amount;

            // Get phase from external_data
            $phase = $exp->external_data['project_work_phase'] ?? null;
            $phase = $phase && in_array($phase, array_map(fn($c) => $c->value, \App\Enums\Projects\WorkPhase::cases())) ? $phase : 'others';

            if (!isset($actualByPhase[$phase])) {
                $actualByPhase[$phase] = ['other' => 0];
            }
            $actualByPhase[$phase]['other'] += $amount;
        }

        $totalSpent    = $actualMaterialCost + $actualOther;
        $remaining     = $approvedBudget - $totalSpent;
        $budgetDiff    = $totalSpent - $approvedBudget;

        // Monthly spend trend (last 12 months) — merge expenses + materials
        $expenseByMonth = $expenses
            ->groupBy(fn($e) => $e->datetime?->format('Y-m') ?? 'unknown')
            ->map(fn($group) => (float) $group->sum(fn($e) => $e->lines->sum('credit')));

        $materialByMonth = $consumptions
            ->groupBy(fn($c) => $c->consumption_date?->format('Y-m') ?? 'unknown')
            ->map(fn($group) => (float) $group->sum(fn($c) => $c->items->sum('total_price')));

        $monthlySpend = $expenseByMonth
            ->mergeRecursive($materialByMonth)
            ->map(fn($v) => is_array($v) ? array_sum($v) : $v)
            ->sortKeys()
            ->take(-12);

        // Calculate totals by type across all phases (for allocation ratios)
        $estTotalByType = [
            'material' => 0,
            'labour'   => 0,
            'indirect' => 0,
        ];

        foreach ($estByPhase as $phaseEstimates) {
            foreach ($phaseEstimates as $type => $amt) {
                $estTotalByType[$type] = ($estTotalByType[$type] ?? 0) + $amt;
            }
        }

        // Phase-wise cost summary with direct allocation (no proportional spread)
        $phaseRows = [];
        foreach (\App\Enums\Projects\WorkPhase::cases() as $phase) {
            $key   = $phase->value;
            $phaseEstimates = $estByPhase[$key] ?? [];

            // Estimated totals for this phase by type
            $phaseMaterialEst = $phaseEstimates['material'] ?? 0;
            $phaseOtherEst    = $phaseEstimates['indirect'] ?? 0;
            $phaseEstTotal    = $phaseMaterialEst + $phaseOtherEst;

            // Actual: material is now allocated to phases via transaction external_data
            $phaseMaterialActual = $materialByPhase[$key] ?? 0;
            $phaseOtherActual    = $actualByPhase[$key]['other'] ?? 0;
            $phaseActualTotal    = $phaseMaterialActual + $phaseOtherActual;

            $phaseRows[] = [
                'phase'            => $phase->label(),
                'estimated'        => $phaseEstTotal,
                'material_est'     => $phaseMaterialEst,
                'other_est'        => $phaseOtherEst,
                'material_actual'  => $phaseMaterialActual,
                'other_actual'     => $phaseOtherActual,
                'actual'           => $phaseActualTotal,
            ];
        }

        // Add "Others" row for unassigned expenses + unphased material consumptions
        $othersMaterialActual = $materialByPhase['others'] ?? 0;
        $othersOtherActual    = $actualByPhase['others']['other'] ?? 0;
        $othersActualTotal    = $othersMaterialActual + $othersOtherActual;

        if ($othersActualTotal > 0) {
            $phaseRows[] = [
                'phase'            => 'Others',
                'estimated'        => 0,
                'material_est'     => 0,
                'other_est'        => 0,
                'material_actual'  => $othersMaterialActual,
                'other_actual'     => $othersOtherActual,
                'actual'           => $othersActualTotal,
            ];
        }

        // Cost composition for donut (percentages)
        $composition = [];
        if ($totalSpent > 0) {
            $composition = [
                'material' => round(($actualMaterialCost / $totalSpent) * 100, 1),
                'other'    => round(($actualOther / $totalSpent) * 100, 1),
            ];
        }

        $project = $this->project;
        $showEditButton = false;
        return view('livewire.admin.projects.project-reports', compact(
            'project', 'approvedBudget', 'totalSpent', 'remaining', 'budgetDiff',
            'estMaterial', 'estLabour', 'estOther',
            'actualMaterialCost', 'actualOther',
            'monthlySpend', 'phaseRows', 'composition', 'showEditButton'
        ))->layout('layouts.admin.admin');
    }
}
