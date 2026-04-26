<?php
// Temporary setup script to create widget directories and files

$projectRoot = 'F:/projects/erp-software';

// Create directories
$directories = [
    $projectRoot . '/app/Livewire/Admin/Dashboard/Widgets',
    $projectRoot . '/resources/views/livewire/admin/dashboard/widgets',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created: $dir\n";
    }
}

// PHP component templates
$components = [
    'TotalSales' => <<<'PHP'
<?php

namespace App\Livewire\Admin\Dashboard\Widgets;

use App\Models\Transaction;
use Livewire\Component;

class TotalSales extends Component
{
    public $totalSales = 0;

    public function mount()
    {
        $this->calculateTotalSales();
    }

    public function calculateTotalSales()
    {
        $this->totalSales = Transaction::query()
            ->where('transaction_type', 'sales')
            ->sum('amount');
    }

    public function render()
    {
        return view('livewire.admin.dashboard.widgets.total-sales');
    }
}
PHP,
    'TotalExpense' => <<<'PHP'
<?php

namespace App\Livewire\Admin\Dashboard\Widgets;

use App\Models\Expense;
use Livewire\Component;

class TotalExpense extends Component
{
    public $totalExpense = 0;

    public function mount()
    {
        $this->calculateTotalExpense();
    }

    public function calculateTotalExpense()
    {
        $this->totalExpense = Expense::query()->sum('amount');
    }

    public function render()
    {
        return view('livewire.admin.dashboard.widgets.total-expense');
    }
}
PHP,
    'StockSummary' => <<<'PHP'
<?php

namespace App\Livewire\Admin\Dashboard\Widgets;

use App\Models\StockBalance;
use Livewire\Component;

class StockSummary extends Component
{
    public $totalItems = 0;
    public $lowStockItems = 0;

    public function mount()
    {
        $this->calculateStockSummary();
    }

    public function calculateStockSummary()
    {
        $this->totalItems = StockBalance::query()->sum('quantity');
        $this->lowStockItems = StockBalance::query()
            ->whereRaw('quantity < reorder_level')
            ->count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.widgets.stock-summary');
    }
}
PHP,
    'ProjectStatus' => <<<'PHP'
<?php

namespace App\Livewire\Admin\Dashboard\Widgets;

use App\Models\Project;
use Livewire\Component;

class ProjectStatus extends Component
{
    public $activeProjects = 0;
    public $completedProjects = 0;

    public function mount()
    {
        $this->calculateProjectStatus();
    }

    public function calculateProjectStatus()
    {
        $this->activeProjects = Project::query()
            ->where('status', '!=', 'completed')
            ->count();
        $this->completedProjects = Project::query()
            ->where('status', 'completed')
            ->count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.widgets.project-status');
    }
}
PHP,
];

// Create component files
foreach ($components as $name => $content) {
    $file = $projectRoot . '/app/Livewire/Admin/Dashboard/Widgets/' . $name . '.php';
    file_put_contents($file, $content);
    echo "Created: $file\n";
}

echo "\nWidget components created successfully!\n";
?>
