<?php

namespace App\Models;

use App\Enums\Notification\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = ['enabled_types', 'web_push_enabled', 'updated_by'];

    protected $casts = [
        'enabled_types'    => 'array',
        'web_push_enabled' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'enabled_types'    => array_column(NotificationType::cases(), 'value'),
            'web_push_enabled' => true,
        ]);
    }

    public function isTypeEnabled(NotificationType $type): bool
    {
        return in_array($type->value, $this->enabled_types ?? [], true);
    }
}
