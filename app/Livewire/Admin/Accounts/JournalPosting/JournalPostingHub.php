<?php

namespace App\Livewire\Admin\Accounts\JournalPosting;

use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\Feature;
use App\Models\JournalPostingGroup;
use App\Models\JournalPostingItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Journal Posting hub — one place to start any accounting entry,
 * grouped by editable categories (journal_posting_groups).
 *
 *  - "Expenses" items are ExpenseCategory rows (same as the Expenses page).
 *  - Other items are JournalPostingItem rows; they open JournalPostingEntryForm
 *    (or a custom link) and carry field rules, behaviour settings and custom fields.
 *  - Built-in shortcut links (Payroll, Purchase Order…) are fixed per system category.
 */
class JournalPostingHub extends Component
{
    use InteractsWithAccountsAccess;

    public const NEW_GROUP = '__new__';

    public const ICONS = [
        'folder' => 'Folder', 'briefcase' => 'Briefcase', 'building' => 'Building', 'megaphone' => 'Megaphone',
        'cog' => 'Settings', 'tool' => 'Tool', 'box' => 'Box', 'package' => 'Package', 'tag' => 'Tag', 'layers' => 'Layers',
    ];

    public const COLORS = [
        'bg-blue-50 border-blue-200 text-blue-700'       => 'Blue',
        'bg-purple-50 border-purple-200 text-purple-700' => 'Purple',
        'bg-orange-50 border-orange-200 text-orange-700' => 'Orange',
        'bg-green-50 border-green-200 text-green-700'    => 'Green',
        'bg-red-50 border-red-200 text-red-700'          => 'Red',
        'bg-pink-50 border-pink-200 text-pink-700'       => 'Pink',
        'bg-indigo-50 border-indigo-200 text-indigo-700' => 'Indigo',
        'bg-yellow-50 border-yellow-200 text-yellow-700' => 'Yellow',
        'bg-gray-50 border-gray-200 text-gray-700'       => 'Gray',
    ];

    /** Default posting type suggested for each built-in category. */
    protected const DEFAULT_TYPES = [
        'contra'   => 'transfer',
        'sales'    => 'income',
        'payment'  => 'expense',
        'purchase' => 'purchase',
        'journal'  => 'adjustment',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'group', except: '')]
    public string $groupFilter = '';

    // ── Item add / edit modal ───────────────────────────────────
    public bool $showModal = false;
    public string $modalTab = 'general';   // general | form | behaviour
    public ?string $editingType = null;     // 'item' | 'expense'
    public ?int $editingId = null;

    public string $form_group = '';
    public string $form_group_name = '';
    public string $form_slug = '';
    public string $form_label = '';
    public string $form_description = '';
    public string $form_icon = 'folder';
    public string $form_color = 'bg-gray-50 border-gray-200 text-gray-700';
    public string $form_kind = 'category';   // adding to Expenses: category (simple) | item (full settings)
    public string $form_feature_type = '';   // expense categories only
    public string $form_debit_source = 'all';   // all | cashbank | feature:<key>
    public string $form_credit_source = 'cashbank';
    public string $form_transaction_type = 'expense';
    public string $form_url = '';
    public array $form_fields = [];        // field rules: key => hidden|optional|required
    public array $form_behaviour = [];     // see JournalPostingItem::BEHAVIOUR_DEFAULTS
    public array $form_custom_fields = []; // [{key,label,type,options,required}]
    public bool $slugManuallyEdited = false;

    // ── Category manager ────────────────────────────────────────
    public bool $showGroupsModal = false;
    public ?int $editingGroupId = null;
    public bool $showGroupForm = false;
    public string $g_name = '';
    public string $g_description = '';
    public string $g_icon = 'custom';
    public string $g_tone = 'indigo';
    public bool $g_active = true;

    public function mount(): void
    {
        $this->authorizePermission('accounts.chart.list');
    }

    public function setGroup(string $group): void
    {
        $this->groupFilter = $this->groupFilter === $group ? '' : $group;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->groupFilter = '';
    }

    // ── Item modal ──────────────────────────────────────────────

    /**
     * The modal edits an ExpenseCategory (simple form) rather than a JournalPostingItem.
     * The Expenses group holds both: editing keeps the row's own kind; when adding
     * to Expenses the user picks the kind (form_kind).
     */
    public function isExpenseForm(): bool
    {
        if ($this->editingId) {
            return $this->editingType === 'expense';
        }

        return $this->form_group === 'expense' && $this->form_kind === 'category';
    }

    public function openAddModal(?string $group = null): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->resetForm();
        $this->form_group = $group ?: ($this->groupFilter ?: 'journal');
        $this->form_transaction_type = self::DEFAULT_TYPES[$this->form_group] ?? 'expense';
        $this->suggestSources();
        $this->showModal = true;
    }

    public function openEditModal(string $type, int $id): void
    {
        $this->resetForm();

        if ($type === 'expense') {
            $this->authorizePermission('accounts.expense.edit');
            $category = ExpenseCategory::findOrFail($id);

            if ($category->isLocked()) {
                $this->dispatch('toast', type: 'error', message: 'Locked categories cannot be edited.');
                return;
            }

            $this->form_group = 'expense';
            $this->form_slug = $category->slug;
            $this->form_label = $category->name;
            $this->form_description = (string) $category->description;
            $this->form_icon = $category->icon ?: 'folder';
            $this->form_color = $category->color ?: $this->form_color;
            $this->form_feature_type = (string) $category->feature_type;
        } else {
            $this->authorizePermission('accounts.chart.list');
            $item = JournalPostingItem::findOrFail($id);

            $this->form_group = $item->group_key;
            $this->form_slug = $item->slug;
            $this->form_label = $item->label;
            $this->form_description = (string) $item->description;
            $this->form_icon = $item->icon ?: 'folder';
            $this->form_color = $item->color ?: $this->form_color;
            $this->form_debit_source = $item->debitSource();
            $this->form_credit_source = $item->creditSource();
            $this->form_transaction_type = $item->transaction_type ?: 'expense';
            $this->form_url = (string) $item->url;
            $this->form_fields = JournalPostingItem::normalizeFieldSettings($item->field_settings);
            $this->form_behaviour = $this->behaviourForForm($item->behaviour);
            $this->form_custom_fields = collect($item->custom_fields ?? [])->map(fn ($f) => [
                'key' => $f['key'] ?? '', 'label' => $f['label'] ?? '', 'type' => $f['type'] ?? 'text',
                'options' => (string) ($f['options'] ?? ''), 'required' => (bool) ($f['required'] ?? false),
            ])->values()->all();
        }

        $this->editingType = $type;
        $this->editingId = $id;
        $this->slugManuallyEdited = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->reset(
            'editingType', 'editingId', 'form_kind', 'form_group_name', 'form_slug', 'form_label', 'form_description',
            'form_icon', 'form_color', 'form_feature_type', 'form_debit_source', 'form_credit_source', 'form_transaction_type', 'form_url', 'slugManuallyEdited',
            'form_custom_fields'
        );
        $this->modalTab = 'general';
        $this->form_fields = JournalPostingItem::normalizeFieldSettings(null);
        $this->form_behaviour = $this->behaviourForForm(null);
    }

    /** Behaviour as form-friendly strings. */
    protected function behaviourForForm(?array $behaviour): array
    {
        $b = JournalPostingItem::normalizeBehaviour($behaviour);

        return [
            'requires_approval'        => (bool) $b['requires_approval'],
            'voucher_format'           => (string) $b['voucher_format'],
            'min_amount'               => $b['min_amount'] === null ? '' : (string) $b['min_amount'],
            'max_amount'               => $b['max_amount'] === null ? '' : (string) $b['max_amount'],
            'default_method'           => (string) $b['default_method'],
            'lock_method'              => (bool) $b['lock_method'],
            'default_debit_account_id'  => (string) ($b['default_debit_account_id'] ?? ''),
            'default_credit_account_id' => (string) ($b['default_credit_account_id'] ?? ''),
            'title_template'           => (string) $b['title_template'],
            'allowed_roles'            => array_values((array) $b['allowed_roles']),
        ];
    }

    public function updatedFormLabel(string $value): void
    {
        if (! $this->slugManuallyEdited) {
            $this->form_slug = Str::slug($value);
        }
    }

    public function updatedFormSlug(string $value): void
    {
        $this->form_slug = Str::slug($value);
        $this->slugManuallyEdited = $value !== '';
    }

    public function updatedFormGroup(string $value): void
    {
        if (! $this->editingId) {
            $this->form_transaction_type = self::DEFAULT_TYPES[$value] ?? $this->form_transaction_type;
            $this->suggestSources();
        }
        if ($this->isExpenseForm()) {
            $this->modalTab = 'general';
        }
    }

    public function updatedFormKind(): void
    {
        $this->resetValidation();
        if ($this->isExpenseForm()) {
            $this->modalTab = 'general';
        }
    }

    // Custom field builder
    public function addCustomField(): void
    {
        $this->form_custom_fields[] = ['key' => '', 'label' => '', 'type' => 'text', 'options' => '', 'required' => false];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->form_custom_fields[$index]);
        $this->form_custom_fields = array_values($this->form_custom_fields);
    }

    public function moveCustomField(int $index, int $direction): void
    {
        $target = $index + $direction;
        if (isset($this->form_custom_fields[$index], $this->form_custom_fields[$target])) {
            [$this->form_custom_fields[$index], $this->form_custom_fields[$target]] = [$this->form_custom_fields[$target], $this->form_custom_fields[$index]];
        }
    }

    /** While adding, the posting type suggests which side is cash & bank. */
    public function updatedFormTransactionType(): void
    {
        if (! $this->editingId) {
            $this->suggestSources();
        }
    }

    protected function suggestSources(): void
    {
        $s = JournalPostingItem::suggestedSources($this->form_transaction_type);
        $this->form_debit_source = $s['debit'];
        $this->form_credit_source = $s['credit'];
    }

    /** Valid account-source values: all, cashbank, feature:<key>. */
    protected function sourceKeys(): array
    {
        return array_merge(
            [JournalPostingItem::SOURCE_ALL, JournalPostingItem::SOURCE_CASHBANK],
            Feature::pluck('key')->map(fn ($k) => JournalPostingItem::SOURCE_FEATURE_PREFIX . $k)->all()
        );
    }

    public function saveItem(): void
    {
        $isExpense = $this->isExpenseForm();
        $this->authorizePermission($isExpense
            ? ($this->editingId ? 'accounts.expense.edit' : 'accounts.expense.create')
            : 'accounts.chart.list');

        // Expense categories only live in Expenses (journal items may live in any category).
        if ($isExpense && $this->form_group !== 'expense') {
            $this->addError('form_group', 'An expense category cannot be moved out of Expenses. Delete it and add it again instead.');
            $this->modalTab = 'general';
            return;
        }

        $slugTable = $isExpense ? 'expense_categories' : 'journal_posting_items';

        $rules = [
            'form_group'            => ['required', 'string', 'max:60'],
            'form_group_name'       => ['required_if:form_group,' . self::NEW_GROUP, 'nullable', 'string', 'max:100'],
            'form_slug'             => ['required', 'string', 'lowercase', 'max:80', Rule::unique($slugTable, 'slug')->ignore($this->editingId)],
            'form_label'            => ['required', 'string', 'max:150'],
            'form_description'      => ['nullable', 'string', 'max:500'],
            'form_icon'             => ['required', Rule::in(array_keys(self::ICONS))],
            'form_color'            => ['required', Rule::in(array_keys(self::COLORS))],
            'form_feature_type'     => ['nullable', 'string', 'exists:features,key'],
            'form_debit_source'     => [$isExpense ? 'nullable' : 'required', Rule::in($this->sourceKeys())],
            'form_credit_source'    => [$isExpense ? 'nullable' : 'required', Rule::in($this->sourceKeys())],
            'form_transaction_type' => [$isExpense ? 'nullable' : 'required', Rule::in(array_column(TransactionType::cases(), 'value'))],
            'form_url'              => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)/i'],
        ];

        if (! $isExpense) {
            $rules += [
                'form_fields'                             => ['array'],
                'form_fields.*'                           => [Rule::in(JournalPostingItem::FIELD_MODES)],
                'form_behaviour.voucher_format'           => ['nullable', 'string', 'max:40', 'regex:/\{#+\}/'],
                'form_behaviour.min_amount'               => ['nullable', 'numeric', 'min:0'],
                'form_behaviour.max_amount'               => ['nullable', 'numeric', 'min:0', 'gte:form_behaviour.min_amount'],
                'form_behaviour.default_method'           => ['required', Rule::in(array_keys(JournalPostingItem::METHODS))],
                // Defaults must be in that side's list, or the entry form would ignore them.
                'form_behaviour.default_debit_account_id'  => ['nullable', Rule::in(JournalPostingItem::accountsForSource($this->form_debit_source)->pluck('id')->map(fn ($id) => (string) $id)->all())],
                'form_behaviour.default_credit_account_id' => ['nullable', Rule::in(JournalPostingItem::accountsForSource($this->form_credit_source)->pluck('id')->map(fn ($id) => (string) $id)->all())],
                'form_behaviour.title_template'           => ['nullable', 'string', 'max:200'],
                'form_behaviour.allowed_roles'            => ['array'],
                'form_behaviour.allowed_roles.*'          => ['exists:roles,name'],
                'form_custom_fields'                      => ['array', 'max:20'],
                'form_custom_fields.*.label'              => ['required', 'string', 'max:60'],
                'form_custom_fields.*.type'               => ['required', Rule::in(array_keys(JournalPostingItem::CUSTOM_FIELD_TYPES))],
                'form_custom_fields.*.options'            => ['nullable', 'required_if:form_custom_fields.*.type,select', 'string', 'max:500'],
            ];
        }

        // Show the tab holding the first error.
        try {
            $this->validate($rules, [
                'form_url.regex'                           => 'Link must start with "/" (e.g. /admin/accounts/expenses) or http(s)://',
                'form_group_name.required_if'              => 'Enter a name for the new category.',
                'form_behaviour.voucher_format.regex'      => 'The format needs a number part like {####}.',
                'form_behaviour.max_amount.gte'            => 'Maximum must be greater than the minimum.',
                'form_custom_fields.*.label.required'      => 'Field label is required.',
                'form_custom_fields.*.options.required_if' => 'Add options (comma separated) for a dropdown.',
            ], [
                'form_group' => 'category', 'form_slug' => 'slug', 'form_label' => 'name', 'form_icon' => 'icon',
                'form_color' => 'color', 'form_feature_type' => 'feature account', 'form_transaction_type' => 'posting type',
                'form_debit_source' => 'debit accounts', 'form_credit_source' => 'credit accounts',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $first = array_key_first($e->errors());
            $this->modalTab = str_starts_with($first, 'form_fields') || str_starts_with($first, 'form_custom_fields')
                ? 'form' : (str_starts_with($first, 'form_behaviour') ? 'behaviour' : 'general');
            throw $e;
        }

        DB::transaction(fn () => $isExpense ? $this->saveExpenseCategory() : $this->saveJournalItem());

        $this->dispatch('toast', type: 'success', message: $this->editingId ? 'Item updated successfully' : 'Item added successfully');
        $this->showModal = false;
    }

    protected function saveExpenseCategory(): void
    {
        $data = [
            'slug'         => $this->form_slug,
            'name'         => trim($this->form_label),
            'description'  => $this->form_description ?: null,
            'icon'         => $this->form_icon,
            'color'        => $this->form_color,
            'feature_type' => $this->form_feature_type ?: null,
            'updated_by'   => Auth::id(),
        ];

        if ($this->editingId) {
            $category = ExpenseCategory::findOrFail($this->editingId);
            abort_if($category->isLocked(), 403, 'Locked categories cannot be edited.');
            $category->update($data);

            return;
        }

        ExpenseCategory::create($data + [
            'is_locked'  => false,
            'is_active'  => true,
            'sort_order' => (int) ExpenseCategory::max('sort_order') + 1,
            'created_by' => Auth::id(),
        ]);
    }

    protected function saveJournalItem(): void
    {
        $groupKey = $this->form_group;

        if ($groupKey === self::NEW_GROUP) {
            $groupKey = $this->createGroup(trim($this->form_group_name))->key;
        }

        $b = $this->form_behaviour;
        $behaviour = [
            'requires_approval'        => (bool) ($b['requires_approval'] ?? true),
            'voucher_format'           => trim((string) ($b['voucher_format'] ?? '')),
            'min_amount'               => ($b['min_amount'] ?? '') === '' ? null : (float) $b['min_amount'],
            'max_amount'               => ($b['max_amount'] ?? '') === '' ? null : (float) $b['max_amount'],
            'default_method'           => $b['default_method'] ?? 'cash',
            'lock_method'              => (bool) ($b['lock_method'] ?? false),
            'default_debit_account_id'  => ($b['default_debit_account_id'] ?? '') ?: null,
            'default_credit_account_id' => ($b['default_credit_account_id'] ?? '') ?: null,
            'title_template'           => trim((string) ($b['title_template'] ?? '')),
            'allowed_roles'            => array_values(array_filter((array) ($b['allowed_roles'] ?? []))),
        ];

        // Stable keys for custom fields (existing keys are kept so saved values stay attached).
        $used = [];
        $customFields = collect($this->form_custom_fields)->map(function ($f) use (&$used) {
            $key = $f['key'] ?: Str::slug(Str::ascii($f['label']), '_') ?: 'field';
            $base = $key;
            $i = 2;
            while (in_array($key, $used, true)) {
                $key = $base . '_' . $i++;
            }
            $used[] = $key;

            return [
                'key'      => $key,
                'label'    => trim($f['label']),
                'type'     => $f['type'],
                'options'  => $f['type'] === 'select' ? trim((string) $f['options']) : null,
                'required' => (bool) $f['required'],
            ];
        })->values()->all();

        $data = [
            'group_key'        => $groupKey,
            'group_name'       => JournalPostingGroup::where('key', $groupKey)->value('name'),
            'slug'             => $this->form_slug,
            'label'            => trim($this->form_label),
            'description'      => $this->form_description ?: null,
            'icon'             => $this->form_icon,
            'color'            => $this->form_color,
            'feature_type'     => JournalPostingItem::sourceFeature($this->form_debit_source) ?? JournalPostingItem::sourceFeature($this->form_credit_source),
            'debit_source'     => $this->form_debit_source,
            'credit_source'    => $this->form_credit_source,
            'transaction_type' => $this->form_transaction_type,
            'url'              => $this->form_url ?: null,
            'field_settings'   => JournalPostingItem::normalizeFieldSettings($this->form_fields),
            'behaviour'        => $behaviour,
            'custom_fields'    => $customFields,
        ];

        if ($this->editingId) {
            $item = JournalPostingItem::findOrFail($this->editingId);
            // Moved to another category → goes to the end of that category.
            if ($item->group_key !== $groupKey) {
                $data['sort_order'] = (int) JournalPostingItem::where('group_key', $groupKey)->max('sort_order') + 1;
            }
            $item->update($data);

            return;
        }

        JournalPostingItem::create($data + [
            'sort_order' => (int) JournalPostingItem::where('group_key', $groupKey)->max('sort_order') + 1,
            'created_by' => Auth::id(),
        ]);
    }

    public function deleteItem(string $type, int $id): void
    {
        if ($type === 'expense') {
            $this->authorizePermission('accounts.expense.delete');
            $category = ExpenseCategory::findOrFail($id);

            if ($category->isLocked()) {
                $this->dispatch('toast', type: 'error', message: 'Locked categories cannot be deleted.');
                return;
            }

            $category->delete();
        } else {
            $this->authorizePermission('accounts.chart.list');
            JournalPostingItem::findOrFail($id)->delete();
        }

        $this->dispatch('toast', type: 'success', message: 'Item removed');
    }

    /** Move an item up / down inside its category. */
    public function moveItem(string $type, int $id, int $direction): void
    {
        $this->authorizePermission('accounts.chart.list');

        if ($type === 'expense') {
            $this->authorizePermission('accounts.expense.edit');
            $siblings = ExpenseCategory::active()->ordered()->get();
        } else {
            $groupKey = JournalPostingItem::whereKey($id)->value('group_key');
            $siblings = JournalPostingItem::where('group_key', $groupKey)->orderBy('sort_order')->orderBy('id')->get();
        }

        $this->swapOrder($siblings->values(), $id, $direction);
    }

    /** Swap a row with its neighbour, then renumber 1..n. */
    protected function swapOrder($rows, int $id, int $direction): void
    {
        $index = $rows->search(fn ($r) => $r->id === $id);
        $target = $index === false ? null : $index + $direction;
        if ($target === null || ! isset($rows[$target])) {
            return;
        }

        $ordered = $rows->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
        foreach ($ordered as $i => $row) {
            $row->update(['sort_order' => $i + 1]);
        }
    }

    // ── Category manager ────────────────────────────────────────

    public function openGroupsModal(): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->showGroupForm = false;
        $this->showGroupsModal = true;
    }

    public function openGroupForm(?int $id = null): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->resetValidation();
        $this->editingGroupId = $id;
        $group = $id ? JournalPostingGroup::findOrFail($id) : null;
        $this->g_name = (string) $group?->name;
        $this->g_description = (string) $group?->description;
        $this->g_icon = $group?->icon ?? 'custom';
        $this->g_tone = $group?->tone ?? 'indigo';
        $this->g_active = $group?->is_active ?? true;
        $this->showGroupForm = true;
    }

    public function saveGroup(): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->validate([
            'g_name'        => ['required', 'string', 'max:100'],
            'g_description' => ['nullable', 'string', 'max:255'],
            'g_icon'        => ['required', Rule::in(array_keys(JournalPostingGroup::ICONS))],
            'g_tone'        => ['required', Rule::in(JournalPostingGroup::TONES)],
        ], [], ['g_name' => 'name']);

        if ($this->editingGroupId) {
            $group = JournalPostingGroup::findOrFail($this->editingGroupId);
            $group->update([
                'name' => trim($this->g_name), 'description' => $this->g_description ?: null,
                'icon' => $this->g_icon, 'tone' => $this->g_tone, 'is_active' => $this->g_active,
            ]);
            JournalPostingItem::where('group_key', $group->key)->update(['group_name' => $group->name]);
        } else {
            $this->createGroup(trim($this->g_name), $this->g_description ?: null, $this->g_icon, $this->g_tone);
        }

        $this->showGroupForm = false;
        $this->dispatch('toast', type: 'success', message: 'Category saved');
    }

    protected function createGroup(string $name, ?string $description = null, string $icon = 'custom', string $tone = 'indigo'): JournalPostingGroup
    {
        $base = 'custom-' . (Str::slug($name) ?: 'category');
        $key = $base;
        $i = 2;
        while (JournalPostingGroup::where('key', $key)->exists()) {
            $key = $base . '-' . $i++;
        }

        return JournalPostingGroup::create([
            'key' => $key, 'name' => $name, 'description' => $description, 'icon' => $icon, 'tone' => $tone,
            'sort_order' => (int) JournalPostingGroup::max('sort_order') + 1, 'is_active' => true, 'is_system' => false,
        ]);
    }

    public function moveGroup(int $id, int $direction): void
    {
        $this->authorizePermission('accounts.chart.list');
        $this->swapOrder(JournalPostingGroup::ordered()->get()->values(), $id, $direction);
    }

    public function toggleGroup(int $id): void
    {
        $this->authorizePermission('accounts.chart.list');
        $group = JournalPostingGroup::findOrFail($id);
        $group->update(['is_active' => ! $group->is_active]);
    }

    public function deleteGroup(int $id): void
    {
        $this->authorizePermission('accounts.chart.list');
        $group = JournalPostingGroup::findOrFail($id);

        if ($group->is_system) {
            $this->dispatch('toast', type: 'error', message: 'Built-in categories cannot be deleted — hide them instead.');
            return;
        }
        if (JournalPostingItem::where('group_key', $group->key)->exists()) {
            $this->dispatch('toast', type: 'error', message: 'Move or delete the items in this category first.');
            return;
        }

        $group->delete();
        $this->dispatch('toast', type: 'success', message: 'Category deleted');
    }

    // ── Data ────────────────────────────────────────────────────

    /** Fixed shortcut links shown in system categories. */
    protected function builtInItems(): array
    {
        return [
            'contra' => [
                ['label' => 'Cash Deposit to Bank', 'description' => 'Deposit cash in hand into a bank account.', 'route' => 'admin.accounts.banks.list', 'permission' => 'accounts.chart.list'],
            ],
            'sales' => [
                ['label' => 'New Property Sale', 'description' => 'Create a sale with booking and installment plan.', 'route' => 'admin.properties.sales.create'],
                ['label' => 'Sales List', 'description' => 'Sales, invoices, schedules and receipts.', 'route' => 'admin.properties.sales.index'],
            ],
            'payment' => [
                ['label' => 'Payment Request', 'description' => 'Raise a payment request for approval.', 'route' => 'admin.accounts.banking.index', 'permission' => 'accounts.chart.list'],
                ['label' => 'Advance Refund', 'description' => 'Refund a customer advance.', 'route' => 'admin.accounts.advance-refund', 'permission' => 'accounts.advance.refund'],
                ['label' => 'Payroll Payments', 'description' => 'Pay approved employee salaries.', 'route' => 'admin.hrm.payroll-payments.index'],
                ['label' => 'Employee Advances', 'description' => 'Give or settle employee advances.', 'route' => 'admin.hrm.employee-advances.index'],
            ],
            'purchase' => [
                ['label' => 'Purchase Order', 'description' => 'Create a purchase order for a supplier.', 'route' => 'admin.inventory.purchase-orders.create'],
                ['label' => 'Purchase Invoices', 'description' => 'Review and approve supplier invoices.', 'route' => 'admin.inventory.purchase-invoices.index'],
                ['label' => 'Purchase Return', 'description' => 'Return goods to a supplier.', 'route' => 'admin.inventory.purchase-returns.create'],
            ],
            'journal' => [
                ['label' => 'All Transactions', 'description' => 'Browse every posted transaction.', 'route' => 'admin.accounts.transactions.index', 'permission' => 'accounts.transaction.list'],
            ],
        ];
    }

    /** Categories (from the DB) with their items. */
    protected function groups(bool $includeHidden = false): array
    {
        $featureLabels = Feature::pluck('label', 'key');
        $groups = JournalPostingGroup::ordered()
            ->when(! $includeHidden, fn ($q) => $q->where('is_active', true))
            ->get()
            ->mapWithKeys(fn (JournalPostingGroup $g) => [$g->key => [
                'id' => $g->id, 'key' => $g->key, 'name' => $g->name, 'tone' => $g->tone, 'icon' => $g->icon,
                'description' => $g->description ?? '', 'is_system' => $g->is_system, 'is_active' => $g->is_active, 'items' => [],
            ]]);

        $journalItems = JournalPostingItem::orderBy('sort_order')->orderBy('id')->get();

        // Expense categories
        if ($groups->has('expense')) {
            // A locked category (hard-coded form) is replaced by a dynamic Expenses item
            // that debits the same feature's accounts; deleting that item brings it back.
            $replacedFeatures = $journalItems->where('group_key', 'expense')
                ->map(fn (JournalPostingItem $item) => JournalPostingItem::sourceFeature($item->debitSource()))
                ->filter()->unique()->all();

            $g = $groups['expense'];
            $g['items'] = ExpenseCategory::active()->ordered()->get()
                ->reject(fn (ExpenseCategory $category) => $category->isLocked()
                    && $category->feature_type && in_array($category->feature_type, $replacedFeatures, true))
                ->map(fn (ExpenseCategory $category) => [
                'id'          => $category->id,
                'source'      => 'expense',
                'locked'      => $category->isLocked(),
                'label'       => $category->name,
                'description' => $category->description ?: 'Record a new ' . Str::lower($category->name) . '.',
                'icon'        => $category->icon,
                'color'       => $category->color,
                'feature'     => $category->feature_type,
                'route'       => 'admin.accounts.expenses.create',
                'params'      => ['category' => $category->slug],
                'permission'  => 'accounts.expense.create',
            ])->values()->all();
            $groups['expense'] = $g;
        }

        // Built-in shortcut links
        foreach ($this->builtInItems() as $key => $items) {
            if ($groups->has($key)) {
                $g = $groups[$key];
                $g['items'] = array_merge($g['items'], $items);
                $groups[$key] = $g;
            }
        }

        // Dynamic items
        $user = auth()->user();
        foreach ($journalItems as $item) {
            if (! $groups->has($item->group_key)) {
                continue;
            }
            // Items limited to other roles stay listed (so they can still be edited)
            // but can't be opened.
            $usable = $item->usableBy($user);
            $g = $groups[$item->group_key];
            $g['items'][] = [
                'id'          => $item->id,
                'source'      => 'item',
                'locked'      => false,
                'label'       => $item->label,
                'description' => $item->description ?? '',
                'icon'        => $item->icon,
                'color'       => $item->color,
                'type'        => $item->transaction_type,
                'dr'          => JournalPostingItem::sourceLabel($item->debitSource(), $featureLabels),
                'cr'          => JournalPostingItem::sourceLabel($item->creditSource(), $featureLabels),
                'url'         => $usable ? ($item->url ?: route('admin.accounts.journal-posting.entry', $item->slug)) : null,
                'no_access'   => ! $usable,
                'direct'      => ! $item->setting('requires_approval'),
                'voucher'     => $item->autoVoucher() ? $item->setting('voucher_format') : null,
                'custom'      => count($item->customFieldDefs()),
                'restricted'  => ! empty($item->setting('allowed_roles')),
            ];
            $groups[$item->group_key] = $g;
        }

        return $groups->values()->all();
    }

    public function render(): View
    {
        $user = auth()->user();
        $term = Str::lower(trim($this->search));
        $featureLabels = Feature::active()->ordered()->pluck('label', 'key');

        $groups = collect($this->groups())
            ->map(function (array $group) use ($user, $term, $featureLabels) {
                $groupMatches = $term !== '' && Str::contains(Str::lower($group['name']), $term);

                $group['items'] = collect($group['items'])
                    ->filter(fn ($item) => empty($item['permission']) || $user?->can($item['permission']))
                    ->map(function ($item) use ($featureLabels) {
                        if (! array_key_exists('url', $item)) {
                            $available = ! empty($item['route']) && Route::has($item['route']);
                            $item['url'] = $available ? route($item['route'], $item['params'] ?? []) : null;
                        }
                        $item['source'] ??= 'builtin';
                        $item['feature_label'] = ! empty($item['feature']) ? ($featureLabels[$item['feature']] ?? $item['feature']) : null;
                        $item['type_label'] = ! empty($item['type']) ? TransactionType::tryFrom($item['type'])?->label() : null;

                        return $item;
                    })
                    ->filter(fn ($item) => $term === '' || $groupMatches
                        || Str::contains(Str::lower($item['label'] . ' ' . $item['description'] . ' ' . $item['feature_label'] . ' ' . ($item['dr'] ?? '') . ' ' . ($item['cr'] ?? '')), $term))
                    ->values()
                    ->all();

                return $group;
            });

        $counts = $groups->mapWithKeys(fn ($g) => [$g['key'] => count($g['items'])]);

        $visibleGroups = $groups
            ->filter(fn ($g) => $this->groupFilter === '' || $g['key'] === $this->groupFilter)
            ->filter(fn ($g) => count($g['items']) > 0)
            ->values();

        $categoryOptions = JournalPostingGroup::ordered()->get(['key', 'name', 'is_active']);

        return view('livewire.admin.accounts.journal-posting.journal-posting-hub', [
            'allGroups'        => $groups,
            'visibleGroups'    => $visibleGroups,
            'counts'           => $counts,
            'categoryOptions'  => $categoryOptions,
            'managedGroups'    => $this->showGroupsModal ? JournalPostingGroup::ordered()->withCount('items')->get() : collect(),
            'iconOptions'      => self::ICONS,
            'colorOptions'     => self::COLORS,
            'featureAccounts'  => $featureLabels,
            'transactionTypes' => collect(TransactionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]),
            'formFieldLabels'  => JournalPostingItem::FORM_FIELDS,
            'methods'          => JournalPostingItem::METHODS,
            'customFieldTypes' => JournalPostingItem::CUSTOM_FIELD_TYPES,
            'roles'            => $this->showModal ? Role::orderBy('name')->pluck('name') : collect(),
            // Default-account pickers only offer accounts from that side's list.
            'debitOptions'     => $this->showModal && $this->modalTab === 'behaviour' ? JournalPostingItem::accountsForSource($this->form_debit_source) : collect(),
            'creditOptions'    => $this->showModal && $this->modalTab === 'behaviour' ? JournalPostingItem::accountsForSource($this->form_credit_source) : collect(),
            'allFeatures'      => Feature::orderBy('sort_order')->orderBy('label')->get(['key', 'label', 'is_active']),
            'voucherPreview'   => trim((string) ($this->form_behaviour['voucher_format'] ?? '')) !== ''
                ? JournalPostingItem::formatVoucher((string) $this->form_behaviour['voucher_format'], 1)
                : null,
        ])->layout('layouts.admin.admin');
    }
}
