<?php

namespace App\Models;

use App\Enums\Accounts\PostingLeg;
use App\Enums\Accounts\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingEvent extends Model
{
    protected $fillable = [
        'key',
        'module',
        'name',
        'description',
        'transaction_type',
        'is_active',
    ];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'is_active'        => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(PostingRule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * A recipe is postable only if it has at least one debit AND one credit leg.
     */
    public function isBalancedRecipe(): bool
    {
        $legs = $this->relationLoaded('rules') ? $this->rules : $this->rules()->get();

        $hasDebit  = $legs->contains(fn (PostingRule $r): bool => $r->leg === PostingLeg::DEBIT);
        $hasCredit = $legs->contains(fn (PostingRule $r): bool => $r->leg === PostingLeg::CREDIT);

        return $hasDebit && $hasCredit;
    }
}
