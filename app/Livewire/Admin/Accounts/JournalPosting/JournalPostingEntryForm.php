<?php

namespace App\Livewire\Admin\Accounts\JournalPosting;

use App\Enums\Accounts\ReportGroup;
use App\Enums\Accounts\TransactionType;
use App\Enums\Projects\WorkPhase;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithFeatureAccounts;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\Account;
use App\Models\BankingPaymentRequest;
use App\Models\Feature;
use App\Models\JournalPostingItem;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Dynamic entry form for a Journal Posting item.
 *
 * Double entry: the user picks a Debit account and a Credit account. Each
 * side's list comes from the item's source for that side — all accounts,
 * cash & bank, or a Feature's account list (debit_source / credit_source).
 *
 * Creates a pending BankingPaymentRequest with pre-stored debit/credit,
 * which posts to the ledger once approved & completed in Banking.
 */
class JournalPostingEntryForm extends Component
{
    use InteractsWithAccountsAccess, InteractsWithFeatureAccounts, WithMediaPicker, \App\Livewire\Concerns\WithProfilePicker;

    public JournalPostingItem $item;

    public ?int $debit_account_id = null;
    public ?int $credit_account_id = null;
    public string $payment_method = 'cash';
    public string $title = '';
    public string $date = '';
    public string $amount = '';
    public string $reference_no = '';
    public string $voucher_no = '';
    public string $party_name = '';
    public string $party_phone = '';
    public string $notes = '';
    public ?int $project_id = null;          // "Project & work phase" field
    public string $project_work_phase = '';
    public array $mediaIds = [];
    public array $custom = [];   // custom field values: key => value

    public function mount(JournalPostingItem $item): void
    {
        $this->item = $item;
        $this->authorizeEntry();

        $this->date = now()->toDateString();

        // Defaults from the item's behaviour settings
        $template = (string) $item->setting('title_template');
        $this->title = $template !== ''
            ? strtr($template, ['{label}' => $item->label, '{date}' => now()->format('d M Y')])
            : $item->label;
        $this->payment_method = (string) $item->setting('default_method') ?: 'cash';
        // Defaults only apply when they are one of the offered options — otherwise
        // the select would look empty while still submitting a hidden account.
        $debitDefault = (int) $item->setting('default_debit_account_id');
        $creditDefault = (int) $item->setting('default_credit_account_id');
        $this->debit_account_id = $debitDefault && $this->debitAccounts()->contains('id', $debitDefault) ? $debitDefault : null;
        $this->credit_account_id = $creditDefault && $this->creditAccounts()->contains('id', $creditDefault) ? $creditDefault : null;

        foreach ($item->customFieldDefs() as $field) {
            $this->custom[$field['key']] = '';
        }

        // Opened from a project page ("Add expense") → project pre-selected.
        $projectId = (int) request()->query('project_id');
        if ($projectId && $item->showsField('project') && Project::whereKey($projectId)->exists()) {
            $this->project_id = $projectId;
        }
    }

    /**
     * Journal Posting users (accounts.chart.list) can use any item; Expenses items are
     * also open to Expenses users (accounts.expense.create), whose category forms
     * now redirect here.
     */
    protected function authorizeEntry(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->can('accounts.chart.list') || ($this->item->isExpenseItem() && $user->can('accounts.expense.create'))),
            403,
            'Unauthorized action.'
        );
        abort_unless($this->item->usableBy($user), 403, 'You are not allowed to use this entry.');
    }

    /** Where Cancel / Save go: the hub, or the Expenses page for Expenses-only users. */
    public function backUrl(): string
    {
        return $this->item->isExpenseItem() && ! auth()->user()?->can('accounts.chart.list')
            ? route('admin.accounts.expenses.index')
            : route('admin.accounts.journal-posting');
    }

    public function transactionType(): TransactionType
    {
        return TransactionType::tryFrom((string) $this->item->transaction_type) ?? TransactionType::EXPENSE;
    }

    public function side(): ReportGroup
    {
        return $this->transactionType()->reportGroup();
    }

    public function save(): void
    {
        $this->authorizeEntry();

        // Hidden fields never carry values.
        foreach (['reference_no', 'voucher_no', 'party_name', 'party_phone', 'notes'] as $field) {
            if (! $this->item->showsField($field)) {
                $this->{$field} = '';
            }
        }
        if (! $this->item->showsField('profile')) {
            $this->clearProfile();
        }
        if (! $this->item->showsField('project')) {
            $this->project_id = null;
            $this->project_work_phase = '';
        }

        abort_unless($this->item->usableBy(auth()->user()), 403);

        // A locked method can't be changed from the default.
        if ($this->item->setting('lock_method')) {
            $this->payment_method = (string) $this->item->setting('default_method');
        }

        // Optional fields per item settings: required | optional.
        $need = fn (string $key) => $this->item->requiresField($key) ? 'required' : 'nullable';

        $amountRules = ['required', 'numeric', 'gt:0'];
        if (($min = $this->item->setting('min_amount')) !== null) {
            $amountRules[] = 'min:' . $min;
        }
        if (($max = $this->item->setting('max_amount')) !== null) {
            $amountRules[] = 'max:' . $max;
        }

        $customRules = [];
        $customNames = [];
        foreach ($this->item->customFieldDefs() as $field) {
            $customRules['custom.' . $field['key']] = array_values(array_filter([
                ! empty($field['required']) ? 'required' : 'nullable',
                match ($field['type']) { 'number' => 'numeric', 'date' => 'date', default => 'string' },
                in_array($field['type'], ['text'], true) ? 'max:255' : ($field['type'] === 'textarea' ? 'max:2000' : null),
                $field['type'] === 'select'
                    ? Rule::in(array_values(array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? ''))))))
                    : null,
            ]));
            $customNames['custom.' . $field['key']] = $field['label'];
        }

        $this->validate([
            // Only the accounts this item offers (feature list / cash-bank list).
            'debit_account_id'  => ['required', 'integer', Rule::in($this->debitAccounts()->pluck('id')->all())],
            'credit_account_id' => ['required', 'integer', Rule::in($this->creditAccounts()->pluck('id')->all()), 'different:debit_account_id'],
            'payment_method'    => ['required', Rule::in(array_keys(JournalPostingItem::METHODS))],
            'title'             => 'required|string|max:200',
            'date'              => 'required|date',
            'amount'            => $amountRules,
            'reference_no'      => $need('reference_no') . '|string|max:100',
            'voucher_no'        => $need('voucher_no') . '|string|max:100',
            'party_name'        => $need('party_name') . '|string|max:200',
            'party_phone'       => $need('party_phone') . '|string|max:20',
            'notes'             => $need('notes') . '|string|max:1000',
            'master_profile_id' => [$need('profile'), 'integer', 'exists:master_profiles,id'],
            'project_id'         => [$need('project'), 'integer', 'exists:projects,id'],
            'project_work_phase' => ['nullable', Rule::in(array_column(WorkPhase::cases(), 'value'))],
        ] + $customRules, [
            'credit_account_id.different' => 'Debit and credit accounts must be different.',
            'master_profile_id.required' => 'Select a profile for this entry.',
            'project_id.required'        => 'Select a project for this entry.',
            'amount.min'                 => 'Amount must be at least ' . number_format((float) $min, 2) . ' for this entry.',
            'amount.max'                 => 'Amount can be at most ' . number_format((float) $max, 2) . ' for this entry.',
        ], [
            'debit_account_id'  => 'debit account',
            'credit_account_id' => 'credit account',
            'party_name'        => strtolower($this->labels()['party']),
            'master_profile_id' => 'profile',
            'project_id'         => 'project',
            'project_work_phase' => 'work phase',
        ] + $customNames);

        [$debitId, $creditId] = [$this->debit_account_id, $this->credit_account_id];

        $amount = round((float) $this->amount, 3);
        $attachments = array_values(array_unique(array_filter(array_map(fn ($m) => (int) (is_array($m) ? ($m['id'] ?? 0) : $m), $this->mediaIds))));

        // Custom field values, stored with their labels so reports stay readable.
        $customValues = collect($this->item->customFieldDefs())
            ->mapWithKeys(fn ($f) => [$f['key'] => ['label' => $f['label'], 'value' => ($this->custom[$f['key']] ?? '') === '' ? null : $this->custom[$f['key']]]])
            ->filter(fn ($v) => $v['value'] !== null)
            ->all();

        $direct = ! $this->item->setting('requires_approval');

        try {
            $request = DB::transaction(function () use ($amount, $attachments, $customValues, $debitId, $creditId, $direct) {
                $voucherNo = $this->item->takeVoucher(\Illuminate\Support\Carbon::parse($this->date)) ?? ($this->voucher_no ?: null);

                $externalData = array_filter([
                    'journal_posting_item' => $this->item->slug,
                    'entry_date'           => $this->date,
                    'debit_account_id'     => $debitId,
                    'credit_account_id'    => $creditId,
                    'payment_method'       => $this->payment_method,
                    'reference_no'         => $this->reference_no ?: null,
                    'attachments'          => $attachments ?: null,
                    'custom_fields'        => $customValues ?: null,
                    // Read by the project expense list / reports (phase breakdown).
                    'project_work_phase'   => $this->project_id && $this->project_work_phase !== '' ? $this->project_work_phase : null,
                ], fn ($v) => $v !== null);

                $request = BankingPaymentRequest::create([
                    'request_no'        => BankingPaymentRequest::generateRequestNo(),
                    'master_profile_id' => $this->master_profile_id,
                    // A project links the entry to it (reference_type/id on the ledger
                    // transaction), like the Project Expense form does.
                    'sourceable_type'   => $this->project_id ? Project::class : null,
                    'sourceable_id'     => $this->project_id ?: null,
                    'source_type'       => $this->transactionType()->value,
                    'amount'            => $amount,
                    'payment_date'      => $this->date,
                    'description'       => $this->title,
                    // The cash side: money in → debit account, otherwise credit account.
                    'account_id'        => $this->side() === ReportGroup::RECEIPT ? $debitId : $creditId,
                    'status'            => $direct ? 'approved' : 'pending',
                    'approved_by'       => $direct ? Auth::id() : null,
                    'approved_at'       => $direct ? now() : null,
                    'notes'             => $this->notes ?: null,
                    'reference_no'      => $this->reference_no ?: null,
                    'voucher_no'        => $voucherNo,
                    'name'              => $this->party_name ?: null,
                    'phone'             => $this->party_phone ?: null,
                    'method'            => $this->payment_method,
                    'requested_by'      => Auth::id(),
                    'external_data'     => $externalData,
                    'debit_account_id'  => $debitId,
                    'debit_amount'      => $amount,
                    'credit_account_id' => $creditId,
                    'credit_amount'     => $amount,
                ]);

                // Direct posting: complete the request now (posts the ledger entry).
                if ($direct) {
                    app(\App\Services\Accounts\BankingTransactionService::class)->completePaymentRequest($request, (int) Auth::id());
                }

                return $request;
            });
        } catch (\DomainException $e) {
            // The transaction rolled back (incl. the voucher number) — reload the item
            // so the in-memory sequence matches the database again.
            $this->item->refresh();
            $this->dispatch('toast', type: 'error', message: 'Could not post: ' . $e->getMessage());
            return;
        }

        $voucher = $request->voucher_no ? " ({$request->voucher_no})" : '';
        $this->dispatch('toast', type: 'success', message: $direct
            ? "{$this->item->label}{$voucher} posted to the ledger."
            : "{$this->item->label}{$voucher} request created. It is now pending approval.");
        $this->redirect($this->backUrl(), navigate: true);
    }

    public function removeAttachment(int $index): void
    {
        unset($this->mediaIds[$index]);
        $this->mediaIds = array_values($this->mediaIds);
    }

    /** Field labels depend on the posting side. */
    public function labels(): array
    {
        return match ($this->side()) {
            ReportGroup::RECEIPT => ['debit' => 'Received in', 'credit' => 'Income / source', 'party' => 'Received From'],
            ReportGroup::PAYMENT => ['debit' => 'Expense / head', 'credit' => 'Paid from', 'party' => 'Paid To'],
            default              => ['debit' => 'Goes to', 'credit' => 'Comes from', 'party' => 'Party'],
        };
    }

    /** Before switching profile, drop party values that were auto-filled from the old one. */
    public function updatingMasterProfileId($value): void
    {
        $old = $this->master_profile_id ? \App\Models\MasterProfile::find($this->master_profile_id) : null;
        if (! $old) {
            return;
        }
        if ($this->party_name === (string) $old->name) {
            $this->party_name = '';
        }
        if ($this->party_phone === (string) $old->phone) {
            $this->party_phone = '';
        }
    }

    /** Picking a profile pre-fills the free-text party fields when they're still empty. */
    public function updatedMasterProfileId($value): void
    {
        // Use the incoming value: clearing sends '' and Livewire unsets the ?int
        // property, so reading $this->master_profile_id here would throw.
        $profile = $value ? \App\Models\MasterProfile::find((int) $value) : null;
        if (! $profile) {
            return;
        }
        if ($this->item->showsField('party_name') && $this->party_name === '') {
            $this->party_name = (string) $profile->name;
        }
        if ($this->item->showsField('party_phone') && $this->party_phone === '') {
            $this->party_phone = (string) $profile->phone;
        }
    }

    /** Active profiles for the searchable party picker: id => "Name — CODE · phone (roles)". */
    protected function profileOptions(): array
    {
        if (! $this->item->showsField('profile')) {
            return [];
        }

        return \App\Models\MasterProfile::query()
            ->with('roles.type:id,name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'phone'])
            ->mapWithKeys(function ($p) {
                $meta  = collect([$p->code, $p->phone])->filter()->join(' · ');
                $roles = $p->roles->pluck('type.name')->filter()->unique()->join(', ');

                return [$p->id => $p->name . ($meta ? " — {$meta}" : '') . ($roles ? " ({$roles})" : '')];
            })
            ->all();
    }

    /** Accounts for one side, from its source: all, cash & bank, or a Feature list. */
    // Lists are resolved once per request (mount, validation and render all reuse them).
    private ?Collection $debitList = null;
    private ?Collection $creditList = null;

    protected function debitAccounts(): Collection
    {
        return $this->debitList ??= JournalPostingItem::accountsForSource($this->item->debitSource());
    }

    protected function creditAccounts(): Collection
    {
        return $this->creditList ??= JournalPostingItem::accountsForSource($this->item->creditSource());
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.journal-posting.journal-posting-entry-form', [
            'debitAccounts'     => $this->debitAccounts(),
            'creditAccounts'    => $this->creditAccounts(),
            'labels'            => $this->labels(),
            'debitSourceLabel'  => JournalPostingItem::sourceLabel($this->item->debitSource(), Feature::pluck('label', 'key')),
            'creditSourceLabel' => JournalPostingItem::sourceLabel($this->item->creditSource(), Feature::pluck('label', 'key')),
            'paymentMethods' => JournalPostingItem::METHODS,
            'profileOptions' => $this->profileOptions(),
            'projectOptions' => $this->item->showsField('project')
                ? Project::orderBy('name')->get(['id', 'name', 'code'])
                    ->mapWithKeys(fn ($p) => [$p->id => $p->name . ($p->code ? " ({$p->code})" : '')])->all()
                : [],
            'workPhases'     => WorkPhase::options(),
        ])->layout('layouts.admin.admin');
    }
}
