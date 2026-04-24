<?php

namespace App\Models;

use App\Enums\Accounts\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'type',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => TransactionType::class,
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class)->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class)->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function collection(): HasOne
    {
        return $this->hasOne(AccountCollection::class, 'transaction_id');
    }

    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function getTotalDebitAttribute(): float
    {
        if (array_key_exists('total_debit', $this->attributes)) {
            return round((float) $this->attributes['total_debit'], 3);
        }

        return round((float) $this->lines()->sum('debit'), 3);
    }

    public function getTotalCreditAttribute(): float
    {
        if (array_key_exists('total_credit', $this->attributes)) {
            return round((float) $this->attributes['total_credit'], 3);
        }

        return round((float) $this->lines()->sum('credit'), 3);
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.0001;
    }
}
