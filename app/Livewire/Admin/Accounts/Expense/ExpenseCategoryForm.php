<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithFeatureAccounts;
use App\Models\ExpenseCategory;
use App\Models\Feature;
use App\Models\FeatureAccountMapping;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExpenseCategoryForm extends Component
{
    use InteractsWithAccountsAccess, InteractsWithFeatureAccounts;

    public ?int $categoryId = null;
    public string $slug = '';
    public string $name = '';
    public string $description = '';
    public string $icon = 'folder';
    public string $color = 'bg-gray-50 border-gray-200 text-gray-700';
    public string $feature_type = '';
    public bool $slugManuallyEdited = false;

    public function isEditing(): bool
    {
        return $this->categoryId !== null;
    }

    protected function rules(): array
    {
        $slugUnique = 'unique:expense_categories,slug';
        if ($this->categoryId) {
            $slugUnique .= ',' . $this->categoryId;
        }

        return [
            'slug' => 'required|string|lowercase|max:80|' . $slugUnique,
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:100',
            'feature_type' => 'nullable|string|exists:features,key',
        ];
    }

    public function mount(?int $category = null): void
    {
        if ($category !== null) {
            $this->authorizePermission('accounts.expense.edit');

            $model = ExpenseCategory::findOrFail($category);

            abort_if($model->isLocked(), 403, 'Locked categories cannot be edited.');

            $this->categoryId = $model->id;
            $this->slug = $model->slug;
            $this->name = $model->name;
            $this->description = (string) $model->description;
            $this->icon = $model->icon;
            $this->color = $model->color;
            $this->feature_type = (string) $model->feature_type;
            $this->slugManuallyEdited = true; // existing slug — treat as manual

            return;
        }

        $this->authorizePermission('accounts.expense.create');
    }

    public function updatedName(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = \Illuminate\Support\Str::slug($value);
        }
    }

    public function updatedSlug(string $value): void
    {
        // Sanitize: force lowercase slug format
        $this->slug = \Illuminate\Support\Str::slug($value);
        $this->slugManuallyEdited = $value !== '';
    }

    public function save(): void
    {
        try {
            if ($this->isEditing()) {
                $this->updateCategory();
                return;
            }

            $this->authorizePermission('accounts.expense.create');

            $this->validate();

            ExpenseCategory::create([
                'slug' => $this->slug,
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
                'color' => $this->color,
                'feature_type' => $this->feature_type ?: null,
                'is_locked' => false,
                'is_active' => true,
                'sort_order' => ExpenseCategory::max('sort_order') + 1 ?? 1,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->dispatch('toast', type: 'success', message: 'Expense category created successfully');
            $this->dispatch('expenseCategoryCreated');
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    protected function updateCategory(): void
    {
        try {
            $this->authorizePermission('accounts.expense.edit');

            $category = ExpenseCategory::findOrFail($this->categoryId);

            abort_if($category->isLocked(), 403, 'Locked categories cannot be edited.');

            $this->validate();

            $category->update([
                'slug' => $this->slug,
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
                'color' => $this->color,
                'feature_type' => $this->feature_type ?: null,
                'updated_by' => Auth::id(),
            ]);

            $this->dispatch('toast', type: 'success', message: 'Expense category updated successfully');
            $this->dispatch('expenseCategoryUpdated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        // Check BEFORE reset() — reset() clears $categoryId making isEditing() always false
        $isEditing = $this->isEditing();
        $this->reset();
        if ($isEditing) {
            $this->dispatch('expenseCategoryUpdated');
        } else {
            $this->dispatch('expenseCategoryCreated');
        }
    }

    public function render(): View
    {
        $iconOptions = [
            'folder' => 'Folder',
            'briefcase' => 'Briefcase',
            'building' => 'Building',
            'megaphone' => 'Megaphone',
            'cog' => 'Settings',
            'tool' => 'Tool',
            'box' => 'Box',
            'package' => 'Package',
            'tag' => 'Tag',
            'layers' => 'Layers',
        ];

        $colorOptions = [
            'bg-blue-50 border-blue-200 text-blue-700' => 'Blue',
            'bg-purple-50 border-purple-200 text-purple-700' => 'Purple',
            'bg-orange-50 border-orange-200 text-orange-700' => 'Orange',
            'bg-green-50 border-green-200 text-green-700' => 'Green',
            'bg-red-50 border-red-200 text-red-700' => 'Red',
            'bg-pink-50 border-pink-200 text-pink-700' => 'Pink',
            'bg-indigo-50 border-indigo-200 text-indigo-700' => 'Indigo',
            'bg-yellow-50 border-yellow-200 text-yellow-700' => 'Yellow',
            'bg-gray-50 border-gray-200 text-gray-700' => 'Gray',
        ];

        $featureLabels = Feature::active()->ordered()->pluck('label', 'key');


        return view('livewire.admin.accounts.expense.expense-category-form', [
            'iconOptions' => $iconOptions,
            'colorOptions' => $colorOptions,
            'featureAccounts' => $featureLabels,
        ]);
    }
}
