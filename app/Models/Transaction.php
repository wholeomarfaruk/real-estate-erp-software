<?php

namespace App\Models;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'datetime',
        'type',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'account_id',
        'debit',
        'credit',
        'name',
        'method',
        'attachments',
        'main_category',
        'sub_category',
        'reference_no',
        'phone',
    ];

    protected $casts = [
        'datetime'    => 'datetime',
        'type'        => TransactionType::class,
        'debit'       => 'decimal:3',
        'credit'      => 'decimal:3',
        'attachments' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
    public function collection(): HasOne
    {
        return $this->hasOne(AccountCollection::class, 'transaction_id');
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }


}
