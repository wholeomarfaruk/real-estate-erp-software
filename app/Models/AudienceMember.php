<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceMember extends Model
{
    protected $fillable = [
        'audience_id', 'member_type', 'member_id',
    ];

    public function audience(): BelongsTo
    {
        return $this->belongsTo(MarketingAudience::class, 'audience_id');
    }

    public function getMemberAttribute(): Lead|Customer|null
    {
        return match ($this->member_type) {
            'lead'     => Lead::find($this->member_id),
            'customer' => Customer::find($this->member_id),
            default    => null,
        };
    }
}
