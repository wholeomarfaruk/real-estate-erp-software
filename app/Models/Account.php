<?php

namespace App\Models;

use App\Enums\Accounts\AccountGroupType;
use App\Enums\Accounts\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use App\Models\AccountReferenceLink;
use App\Models\TransactionLine;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'group',
        'is_active',
        'is_locked',
        'sub_type',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'group' => AccountGroupType::class,
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function referenceKeys(): HasMany
    {
        return $this->hasMany(AccountReferenceLink::class)->orderBy('reference_key');
    }

    public function allowedReferences(): Collection
    {
        $keys = $this->relationLoaded('referenceKeys')
            ? $this->referenceKeys->pluck('reference_key')->all()
            : $this->referenceKeys()->pluck('reference_key')->all();

        return collect(account_reference_config())->only($keys);
    }
    public function referenceLinks()
    {
        return $this->hasMany(AccountReferenceLink::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_account_id');
    }

    public function paymentPurposeAccounts(): HasMany
    {
        return $this->hasMany(Payment::class, 'purpose_account_id');
    }

    public function collectionAccounts(): HasMany
    {
        return $this->hasMany(AccountCollection::class, 'collection_account_id');
    }

    public function collectionTargetAccounts(): HasMany
    {
        return $this->hasMany(AccountCollection::class, 'target_account_id');
    }

    public function expenseAccounts(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_account_id');
    }

    public function expensePaymentAccounts(): HasMany
    {
        return $this->hasMany(Expense::class, 'payment_account_id');
    }
    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class);
    }
    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    public function enabledForFeatures(): HasMany
    {
        return $this->hasMany(FeatureAccountMapping::class, 'parent_account_id');
    }

    public function featureMappings(): HasMany
    {
        return $this->hasMany(FeatureAccountMapping::class, 'child_account_id');
    }

    public function getBalanceAttribute(): float
    {
        // Balance is computed from the per-account ledger lines (double-entry),
        // which hold the true debit/credit movement for this account.
        return round(
            (float) $this->lines()->sum('debit') - (float) $this->lines()->sum('credit'),
            3
        );
    }
}
