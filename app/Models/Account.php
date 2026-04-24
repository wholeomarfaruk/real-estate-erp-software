<?php

namespace App\Models;

use App\Enums\Accounts\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function transactionLines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
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
}
