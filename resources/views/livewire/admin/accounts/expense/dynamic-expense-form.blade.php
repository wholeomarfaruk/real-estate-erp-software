<div>
    {{-- Dynamic wrapper that loads the appropriate form based on category type --}}

    @if ($category)
        @if ($category->is_locked && $category->form_component)
            {{-- Load locked category form (ProjectExpenseForm, OfficeExpenseForm, MarketingExpenseForm) --}}
            <livewire:{{ str($category->form_component)->after('\\')->snake() }} :key="'locked-' . $category->id" />
        @else
            {{-- Load generic form for dynamic categories --}}
            <livewire:admin.accounts.expense.generic-expense-form :category="$category" :key="'dynamic-' . $category->id" />
        @endif
    @else
        <div class="text-center py-12">
            <p class="text-gray-500">Category not found</p>
        </div>
    @endif
</div>
