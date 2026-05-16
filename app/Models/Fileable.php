<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Fileable extends Model
{
    protected $fillable = ['file_id', 'fileable_id', 'fileable_type', 'category', 'caption', 'is_cover', 'sort_order'];

    protected $casts = [
        'is_cover'   => 'boolean',
        'sort_order' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
