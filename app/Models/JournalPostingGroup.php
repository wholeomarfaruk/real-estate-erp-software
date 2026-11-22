<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalPostingGroup extends Model
{
    /** Colour tones available for categories (rendered by the hub view). */
    public const TONES = ['rose', 'sky', 'emerald', 'amber', 'violet', 'slate', 'indigo'];

    /** Icon keys available for categories (rendered by the hub view). */
    public const ICONS = [
        'expense' => 'Money', 'contra' => 'Transfer', 'sales' => 'Building', 'payment' => 'Card',
        'purchase' => 'Cart', 'journal' => 'Book', 'custom' => 'Grid', 'folder' => 'Folder',
        'briefcase' => 'Briefcase', 'megaphone' => 'Megaphone', 'tag' => 'Tag', 'layers' => 'Layers',
    ];

    protected $fillable = ['key', 'name', 'description', 'icon', 'tone', 'sort_order', 'is_active', 'is_system'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function items()
    {
        return $this->hasMany(JournalPostingItem::class, 'group_key', 'key');
    }
}
