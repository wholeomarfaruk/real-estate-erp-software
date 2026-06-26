<?php

namespace App\Models;

use App\Enums\Accounts\EntryWorkflow;
use App\Services\Accounts\FeatureAccountService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class AccountEntryType extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'category_key', 'icon',
        'workflow', 'transaction_type', 'accounting_event_key',
        'debit_feature_type', 'debit_account_group', 'debit_account_type',
        'credit_feature_type', 'credit_account_group', 'credit_account_type',
        'is_locked', 'form_component', 'permission',
        'sort_order', 'is_active', 'is_visible',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'workflow' => EntryWorkflow::class,
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountEntryCategory::class, 'category_key', 'key');
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function isDynamic(): bool
    {
        return !$this->is_locked;
    }

    public function hasHardcodedForm(): bool
    {
        return $this->is_locked && $this->form_component !== null;
    }

    public function usesBankingRequest(): bool
    {
        return $this->workflow === EntryWorkflow::BANKING_REQUEST;
    }

    public function usesDirectLedger(): bool
    {
        return $this->workflow === EntryWorkflow::DIRECT_LEDGER;
    }

    public function usesPostingEngine(): bool
    {
        return $this->workflow === EntryWorkflow::POSTING_ENGINE;
    }

    public function resolveDebitAccounts(): Collection
    {
        if ($this->debit_feature_type) {
            return app(FeatureAccountService::class)
                ->getAllEnabledChildrenForFeature($this->debit_feature_type);
        }
        if ($this->debit_account_group) {
            return Account::where('group', $this->debit_account_group)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        if ($this->debit_account_type) {
            $types = explode(',', $this->debit_account_type);
            return Account::whereIn('type', $types)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        return Account::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function resolveCreditAccounts(): Collection
    {
        if ($this->credit_feature_type) {
            return app(FeatureAccountService::class)
                ->getAllEnabledChildrenForFeature($this->credit_feature_type);
        }
        if ($this->credit_account_group) {
            return Account::where('group', $this->credit_account_group)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        if ($this->credit_account_type) {
            $types = explode(',', $this->credit_account_type);
            return Account::whereIn('type', $types)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }
        return Account::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
