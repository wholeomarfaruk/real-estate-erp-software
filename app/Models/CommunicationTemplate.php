<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'subject', 'body', 'variables', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'variables'  => 'array',
        'is_active'  => 'boolean',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'template_id');
    }

    /**
     * Replace {variable} placeholders with actual data from lead/customer.
     */
    public function render(array $data): string
    {
        $body = $this->body;
        foreach ($data as $key => $value) {
            $body = str_replace('{' . $key . '}', $value ?? '', $body);
        }
        return $body;
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'sms'   => '#3B82F6',
            'email' => '#8B5CF6',
            'both'  => '#10B981',
            default => '#6B7280',
        };
    }
}
