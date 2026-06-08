<?php

namespace App\Models;

use App\Models\Concerns\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes, HasFiles;

    protected $fillable = [
        'lead_no',
        'name',
        'phone',
        'email',
        'address',
        'lead_source_id',
        'project_id',
        'assigned_to',
        'budget_min',
        'budget_max',
        'status',
        'closed_reason',
        'converted_customer_id',
        'converted_at',
        'score',
        'social_profiles',
        'extra_data',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'social_profiles' => 'array',
        'extra_data'      => 'array',
        'attachments'     => 'array',
        'converted_at'    => 'datetime',
        'budget_min'      => 'decimal:2',
        'budget_max'      => 'decimal:2',
        'score'           => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Lead $lead) {
            $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
            $lead->lead_no = 'LEAD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
        });

        static::saving(function (Lead $lead) {
            $lead->score = $lead->calculateScore();
        });
    }

    public function calculateScore(): int
    {
        $score  = 0;
        $social = $this->social_profiles ?? [];
        $extra  = $this->extra_data ?? [];
        $files  = $this->attachments['file_ids'] ?? [];

        if (! empty($social['facebook'])) $score += 10;
        if (! empty($social['whatsapp'])) $score += 5;
        if (! empty($extra['income_range'])) $score += 10;
        if (! empty($extra['occupation'])) $score += 5;
        if (count($files) > 0) $score += 5;
        if (! empty($this->email)) $score += 5;

        return min($score, 100);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class)->orderBy('scheduled_at', 'asc');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'related_id')
            ->where('related_type', 'lead')
            ->orderBy('due_at', 'asc');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new'         => '#6B7280',
            'contacted'   => '#3B82F6',
            'qualified'   => '#8B5CF6',
            'site_visit'  => '#F59E0B',
            'negotiation' => '#EF4444',
            'won'         => '#10B981',
            'lost'        => '#DC2626',
            default       => '#6B7280',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new'         => 'New',
            'contacted'   => 'Contacted',
            'qualified'   => 'Qualified',
            'site_visit'  => 'Site Visit',
            'negotiation' => 'Negotiation',
            'won'         => 'Won',
            'lost'        => 'Lost',
            default       => ucfirst($this->status),
        };
    }
}
