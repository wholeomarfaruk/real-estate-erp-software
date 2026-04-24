<?php

namespace App\Models;

use App\Enums\Accounts\CollectionType;
use App\Enums\Accounts\EntryMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCollection extends Model
{
    use HasFactory;

    protected $table = 'collections';

    protected $fillable = [
        'transaction_id',
        'collection_no',
        'date',
        'method',
        'collection_account_id',
        'target_account_id',
        'amount',
        'payer_name',
        'collection_type',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'method' => EntryMethod::class,
        'collection_type' => CollectionType::class,
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function collectionAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'collection_account_id');
    }

    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
