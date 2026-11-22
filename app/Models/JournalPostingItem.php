<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JournalPostingItem extends Model
{
    /** Configurable entry-form fields (key => label). Accounts, amount, date, title are always required. */
    public const FORM_FIELDS = [
        'profile'      => 'Party (profile)',
        'party_name'   => 'Party name',
        'party_phone'  => 'Phone',
        'reference_no' => 'Reference no',
        'voucher_no'   => 'Voucher no',
        'notes'        => 'Notes',
        'project'      => 'Project & work phase',
    ];

    public const FIELD_MODES = ['hidden', 'optional', 'required'];

    /** Fields whose default mode isn't "optional" (so adding a field doesn't change existing items). */
    public const FIELD_DEFAULTS = [
        'project' => 'hidden',
    ];

    public static function defaultFieldMode(string $key): string
    {
        return self::FIELD_DEFAULTS[$key] ?? 'optional';
    }

    public const METHODS = [
        'cash' => 'Cash', 'bank' => 'Bank Transfer', 'cheque' => 'Cheque',
        'mobile_banking' => 'Mobile Banking', 'journal' => 'Journal (non-cash)',
    ];

    /** Where a side's account list comes from: all accounts, cash & bank, or a Feature (feature:<key>). */
    public const SOURCE_ALL = 'all';
    public const SOURCE_CASHBANK = 'cashbank';
    public const SOURCE_FEATURE_PREFIX = 'feature:';
    public const CASHBANK_TYPES = ['cash', 'bank', 'mfs', 'wallet'];

    public const CUSTOM_FIELD_TYPES =['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Dropdown', 'textarea' => 'Long text'];

    /** Behaviour settings with defaults. */
    public const BEHAVIOUR_DEFAULTS = [
        'requires_approval'        => true,   // false = post to the ledger immediately
        'voucher_format'           => '',     // e.g. JV-{YYYY}-{####}; empty = typed manually
        'min_amount'               => null,
        'max_amount'               => null,
        'default_method'            => 'cash',
        'lock_method'               => false,
        'default_debit_account_id'  => null,
        'default_credit_account_id' => null,
        'title_template'           => '',     // default title; {label} {date}
        'allowed_roles'            => [],     // empty = everyone with access
    ];

    protected $fillable = [
        'group_key',
        'group_name',
        'slug',
        'label',
        'description',
        'icon',
        'color',
        'feature_type',
        'debit_source',
        'credit_source',
        'transaction_type',
        'url',
        'field_settings',
        'behaviour',
        'custom_fields',
        'sort_order',
        'created_by',
        'expense_category_id',
    ];

    protected $casts = [
        'field_settings' => 'array',
        'behaviour'      => 'array',
        'custom_fields'  => 'array',
    ];

    /** The expense category this item replaces (its old form redirects here). */
    public function expenseCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /** Expense items can also be used with the expense permission (Expenses page users). */
    public function isExpenseItem(): bool
    {
        return $this->group_key === 'expense';
    }

    // ── Field rules ─────────────────────────────────────────────

    /** Mode of one form field: hidden | optional | required (default optional). */
    public function fieldMode(string $key): string
    {
        // An auto-numbered voucher is never typed in.
        if ($key === 'voucher_no' && $this->autoVoucher()) {
            return 'hidden';
        }

        $mode = $this->field_settings[$key] ?? null;

        return in_array($mode, self::FIELD_MODES, true) ? $mode : self::defaultFieldMode($key);
    }

    public function showsField(string $key): bool
    {
        return $this->fieldMode($key) !== 'hidden';
    }

    public function requiresField(string $key): bool
    {
        return $this->fieldMode($key) === 'required';
    }

    /** Full settings with defaults filled in. */
    public static function normalizeFieldSettings(?array $settings): array
    {
        return collect(self::FORM_FIELDS)->mapWithKeys(fn ($label, $key) => [
            $key => in_array($settings[$key] ?? null, self::FIELD_MODES, true) ? $settings[$key] : self::defaultFieldMode($key),
        ])->all();
    }

    // ── Behaviour ───────────────────────────────────────────────

    public static function normalizeBehaviour(?array $behaviour): array
    {
        return array_merge(self::BEHAVIOUR_DEFAULTS, array_intersect_key($behaviour ?? [], self::BEHAVIOUR_DEFAULTS));
    }

    public function setting(string $key): mixed
    {
        return self::normalizeBehaviour($this->behaviour)[$key];
    }

    public function autoVoucher(): bool
    {
        return trim((string) $this->setting('voucher_format')) !== '';
    }

    /** Whether a user may use this item (role restriction; superadmin always may). */
    public function usableBy(?User $user): bool
    {
        $roles = array_filter((array) $this->setting('allowed_roles'));

        return ! $roles || ($user && ($user->hasRole('superadmin') || $user->hasAnyRole($roles)));
    }

    /** Render a voucher number from the format and a sequence number. */
    public static function formatVoucher(string $format, int $seq, ?\DateTimeInterface $date = null): string
    {
        $date ??= now();
        $out = strtr($format, [
            '{YYYY}' => $date->format('Y'),
            '{YY}'   => $date->format('y'),
            '{MM}'   => $date->format('m'),
        ]);

        return preg_replace_callback('/\{(#+)\}/', fn ($m) => str_pad((string) $seq, strlen($m[1]), '0', STR_PAD_LEFT), $out);
    }

    /** Next voucher number without consuming it (for previews). */
    public function previewVoucher(?\DateTimeInterface $date = null): ?string
    {
        if (! $this->autoVoucher()) {
            return null;
        }
        $date ??= now();
        $yearly = $this->formatResetsYearly();
        $seq = ($yearly && (int) $this->voucher_seq_year !== (int) $date->format('Y')) ? 1 : $this->voucher_seq + 1;

        return self::formatVoucher($this->setting('voucher_format'), $seq, $date);
    }

    /** Reserve the next voucher number (row-locked; call inside a DB transaction). */
    public function takeVoucher(?\DateTimeInterface $date = null): ?string
    {
        if (! $this->autoVoucher()) {
            return null;
        }
        $date ??= now();

        return DB::transaction(function () use ($date) {
            $row = static::whereKey($this->id)->lockForUpdate()->first();
            $year = (int) $date->format('Y');
            $seq = ($row->formatResetsYearly() && (int) $row->voucher_seq_year !== $year) ? 1 : $row->voucher_seq + 1;

            $row->forceFill(['voucher_seq' => $seq, 'voucher_seq_year' => $year])->save();
            $this->voucher_seq = $seq;
            $this->voucher_seq_year = $year;

            return self::formatVoucher($row->setting('voucher_format'), $seq, $date);
        });
    }

    protected function formatResetsYearly(): bool
    {
        $format = (string) $this->setting('voucher_format');

        return str_contains($format, '{YYYY}') || str_contains($format, '{YY}');
    }

    // ── Double-entry account sources ────────────────────────────

    /** Suggested sources for a posting type: money in → Dr cash/bank, money out → Cr cash/bank. */
    public static function suggestedSources(?string $transactionType): array
    {
        $type = \App\Enums\Accounts\TransactionType::tryFrom((string) $transactionType);

        return match ($type?->reportGroup()) {
            \App\Enums\Accounts\ReportGroup::RECEIPT => ['debit' => self::SOURCE_CASHBANK, 'credit' => self::SOURCE_ALL],
            \App\Enums\Accounts\ReportGroup::PAYMENT => ['debit' => self::SOURCE_ALL, 'credit' => self::SOURCE_CASHBANK],
            default => $type === \App\Enums\Accounts\TransactionType::TRANSFER
                ? ['debit' => self::SOURCE_CASHBANK, 'credit' => self::SOURCE_CASHBANK]
                : ['debit' => self::SOURCE_ALL, 'credit' => self::SOURCE_ALL],
        };
    }

    public function debitSource(): string
    {
        return $this->debit_source ?: self::suggestedSources($this->transaction_type)['debit'];
    }

    public function creditSource(): string
    {
        return $this->credit_source ?: self::suggestedSources($this->transaction_type)['credit'];
    }

    /**
     * Accounts offered for one side, from its source. Single source of truth for the
     * entry form and the hub's default-account pickers.
     *   all      → every active account
     *   cashbank → active cash / bank / MFS / wallet accounts
     *   feature  → the feature's enabled accounts (active only, no duplicates);
     *              falls back to all active accounts when the feature has none
     */
    public static function accountsForSource(?string $source): \Illuminate\Support\Collection
    {
        if ($feature = self::sourceFeature($source)) {
            $accounts = app(\App\Services\Accounts\FeatureAccountService::class)
                ->getAllEnabledChildrenForFeature($feature)
                ->filter(fn ($a) => $a && $a->is_active)   // skip deleted / inactive accounts
                ->unique('id')
                ->values();

            if ($accounts->isNotEmpty()) {
                return $accounts;
            }
        }

        return Account::where('is_active', true)
            ->when($source === self::SOURCE_CASHBANK, fn ($q) => $q->whereIn('type', self::CASHBANK_TYPES))
            ->orderBy('code')
            ->get(['id', 'code', 'type', 'name', 'is_active']);
    }

    /** Feature key of a source, or null. */
    public static function sourceFeature(?string $source): ?string
    {
        return $source && str_starts_with($source, self::SOURCE_FEATURE_PREFIX)
            ? substr($source, strlen(self::SOURCE_FEATURE_PREFIX)) : null;
    }

    /** Human label of a source, given feature labels (key => label). */
    public static function sourceLabel(?string $source, $featureLabels = []): string
    {
        return match (true) {
            $source === self::SOURCE_CASHBANK => 'Cash & bank',
            ($key = self::sourceFeature($source)) !== null => $featureLabels[$key] ?? $key,
            default => 'All accounts',
        };
    }

    // ── Custom fields ───────────────────────────────────────────

    /** @return array<int, array{key:string,label:string,type:string,options:?string,required:bool}> */
    public function customFieldDefs(): array
    {
        return array_values(array_filter($this->custom_fields ?? [], fn ($f) => ! empty($f['key']) && ! empty($f['label'])));
    }
}
