<?php

namespace App\Models;

use App\Enums\Accounts\AccountSource;
use App\Enums\Accounts\AmountSource;
use App\Enums\Accounts\PostingLeg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRule extends Model
{
    protected $fillable = [
        'accounting_event_id',
        'leg',
        'account_source',
        'account_id',
        'runtime_slot',
        'amount_source',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'leg'            => PostingLeg::class,
        'account_source' => AccountSource::class,
        'amount_source'  => AmountSource::class,
        'sort_order'     => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class, 'accounting_event_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isFixed(): bool
    {
        return $this->account_source === AccountSource::FIXED;
    }

    public function isRuntime(): bool
    {
        return $this->account_source === AccountSource::RUNTIME;
    }
}
