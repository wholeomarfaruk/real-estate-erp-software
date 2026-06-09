<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsGateway extends Model
{
    protected $fillable = ['name', 'provider', 'credentials', 'is_active', 'created_by', 'updated_by'];

    protected $casts = [
        'credentials' => 'array',
        'is_active'   => 'boolean',
    ];

    protected $hidden = ['credentials'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
