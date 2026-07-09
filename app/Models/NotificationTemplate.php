<?php

namespace App\Models;

use App\Enums\Notification\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'title', 'body', 'variables', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'type'       => NotificationType::class,
        'variables'  => 'array',
        'is_active'  => 'boolean',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Replace {variable} placeholders with actual data.
     */
    public function render(array $data): array
    {
        $title = $this->title;
        $body  = $this->body;

        foreach ($data as $key => $value) {
            $title = str_replace('{' . $key . '}', $value ?? '', $title);
            $body  = str_replace('{' . $key . '}', $value ?? '', $body);
        }

        return ['title' => $title, 'body' => $body];
    }
}
