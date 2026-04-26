@echo off
cd /d F:\projects\erp-software

REM Create Widgets directories
if not exist app\Livewire\Admin\Dashboard\Widgets mkdir app\Livewire\Admin\Dashboard\Widgets
if not exist resources\views\livewire\admin\dashboard\widgets mkdir resources\views\livewire\admin\dashboard\widgets

REM Create widget Livewire components using artisan
php artisan make:livewire Admin/Dashboard/Widgets/TotalSales
php artisan make:livewire Admin/Dashboard/Widgets/TotalExpense  
php artisan make:livewire Admin/Dashboard/Widgets/StockSummary
php artisan make:livewire Admin/Dashboard/Widgets/ProjectStatus

echo Widget components created successfully!
pause
