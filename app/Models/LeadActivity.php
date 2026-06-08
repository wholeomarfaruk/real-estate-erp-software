<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id', 'type', 'description', 'old_value', 'new_value', 'created_by',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'call'          => 'call',
            'email'         => 'email',
            'whatsapp'      => 'whatsapp',
            'sms'           => 'sms',
            'site_visit'    => 'site_visit',
            'meeting'       => 'meeting',
            'status_change' => 'status_change',
            'assigned'      => 'assigned',
            'converted'     => 'converted',
            default         => 'note',
        };
    }
}
