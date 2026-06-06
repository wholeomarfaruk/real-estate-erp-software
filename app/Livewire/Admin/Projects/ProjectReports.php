<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\Projects\EstimateStatus;
use App\Models\Expense;
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

        // Actual: material from stock consumptions
        $consumptions = StockConsumption::with('items.product')
            ->where('project_id', $this->project->id)
            ->where('status', 'posted')
            ->get();

        $actualMaterialCost = 0;
        foreach ($consumptions as $con) {
            foreach ($con->items as $item) {
                $actualMaterialCost += (float)($item->total_price ?? ($item->quantity * ($item->unit_price ?? 0)));
            }
        }

        // Actual: labour + other from posted expense transactions (ledger)
        $expenses = $this->project->expenseTransactions()
            ->with('transactionCategory')
            ->get();

        $actualLabour = 0;
        $actualOther  = 0;
        $actualByPhase = []; // phase => ['labour' => X, 'other' => Y]

        foreach ($expenses as $exp) {
            $name = strtolower($exp->transactionCategory?->name ?? '');
            $isLabour = str_contains($name, 'labour') || str_contains($name, 'labor');
            $type = $isLabour ? 'labour' : 'other';
            $amount = (float)$exp->credit;

            if ($isLabour) {
                $actualLabour += $amount;
            } else {
                $actualOther += $amount;
            }

            // Get phase from external_data
            $phase = $exp->external_data['project_work_phase'] ?? null;
            $phase = $phase && in_array($phase, array_map(fn($c) => $c->value, \App\Enums\Projects\WorkPhase::cases())) ? $phase : 'others';

            if (!isset($actualByPhase[$phase])) {
                $actualByPhase[$phase] = ['labour' => 0, 'other' => 0];
            }
            $actualByPhase[$phase][$type] += $amount;
        }

        $totalSpent    = $actualMaterialCost + $actualLabour + $actualOther;
        $remaining     = $approvedBudget - $totalSpent;
        $budgetDiff    = $totalSpent - $approvedBudget;

        // Monthly spend trend (last 12 months)
        $monthlySpend = $expenses
            ->groupBy(fn($e) => $e->datetime?->format('Y-m') ?? 'unknown')
            ->map(fn($group) => $group->sum('credit'))
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
            $phaseLabourEst   = $phaseEstimates['labour'] ?? 0;
            $phaseOtherEst    = $phaseEstimates['indirect'] ?? 0;
            $phaseEstTotal    = $phaseMaterialEst + $phaseLabourEst + $phaseOtherEst;

            // Actual: from stock consumptions (material) and from phase-tagged transactions (labour/other)
            $phaseMaterialActual = 0;
            $phaseLabourActual   = $actualByPhase[$key]['labour'] ?? 0;
            $phaseOtherActual    = $actualByPhase[$key]['other'] ?? 0;
            $phaseActualTotal    = $phaseMaterialActual + $phaseLabourActual + $phaseOtherActual;

            $phaseRows[] = [
                'phase'            => $phase->label(),
                'estimated'        => $phaseEstTotal,
                'material_est'     => $phaseMaterialEst,
                'labour_est'       => $phaseLabourEst,
                'other_est'        => $phaseOtherEst,
                'material_actual'  => $phaseMaterialActual,
                'labour_actual'    => $phaseLabourActual,
                'other_actual'     => $phaseOtherActual,
                'actual'           => $phaseActualTotal,
            ];
        }

        // Add "Others" row for unassigned expenses
        $othersMaterialActual = 0;
        $othersLabourActual   = $actualByPhase['others']['labour'] ?? 0;
        $othersOtherActual    = $actualByPhase['others']['other'] ?? 0;
        $othersActualTotal    = $othersMaterialActual + $othersLabourActual + $othersOtherActual;

        if ($othersActualTotal > 0) {
            $phaseRows[] = [
                'phase'            => 'Others',
                'estimated'        => 0,
                'material_est'     => 0,
                'labour_est'       => 0,
                'other_est'        => 0,
                'material_actual'  => $othersMaterialActual,
                'labour_actual'    => $othersLabourActual,
                'other_actual'     => $othersOtherActual,
                'actual'           => $othersActualTotal,
            ];
        }

        // Cost composition for donut (percentages)
        $composition = [];
        if ($totalSpent > 0) {
            $composition = [
                'material' => round(($actualMaterialCost / $totalSpent) * 100, 1),
                'labour'   => round(($actualLabour / $totalSpent) * 100, 1),
                'other'    => round(($actualOther / $totalSpent) * 100, 1),
            ];
        }

        $project = $this->project;
        return view('livewire.admin.projects.project-reports', compact(
            'project', 'approvedBudget', 'totalSpent', 'remaining', 'budgetDiff',
            'estMaterial', 'estLabour', 'estOther',
            'actualMaterialCost', 'actualLabour', 'actualOther',
            'monthlySpend', 'phaseRows', 'composition'
        ))->layout('layouts.admin.admin');
    }
}
