<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MasterProfileRole extends Model
{
    protected $fillable = [
        'master_profile_id', 'profile_type_id', 'linkable_type', 'linkable_id', 'reference', 'since', 'status', 'data',
    ];

    protected $casts = [
        'data'  => 'array',
        'since' => 'date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProfileType::class, 'profile_type_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
