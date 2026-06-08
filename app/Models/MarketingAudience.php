<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingAudience extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'type', 'filters', 'member_count', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'filters'      => 'array',
        'is_active'    => 'boolean',
        'member_count' => 'integer',
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(AudienceMember::class, 'audience_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'audience_id');
    }

    /**
     * Resolve leads/customers matching this audience's filters.
     * Returns a collection of ['type'=>'lead'|'customer', 'id'=>int, 'name'=>string, 'phone'=>string, 'email'=>string]
     */
    public function resolveMembers(): \Illuminate\Support\Collection
    {
        if ($this->type === 'static') {
            return $this->members()->get()->map(function (AudienceMember $m) {
                $model = $m->member_type === 'lead'
                    ? Lead::find($m->member_id)
                    : Customer::find($m->member_id);
                return $model ? [
                    'type'  => $m->member_type,
                    'id'    => $m->member_id,
                    'name'  => $model->name,
                    'phone' => $model->phone,
                    'email' => $model->email ?? null,
                ] : null;
            })->filter();
        }

        // Dynamic — apply filters
        $filters = $this->filters ?? [];
        $results = collect();

        // Leads
        $leadsQ = Lead::query();
        if (!empty($filters['lead_status'])) {
            $leadsQ->whereIn('status', (array) $filters['lead_status']);
        }
        if (!empty($filters['project_id'])) {
            $leadsQ->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['budget_min'])) {
            $leadsQ->where('budget_min', '>=', $filters['budget_min']);
        }
        if (!empty($filters['source_id'])) {
            $leadsQ->where('lead_source_id', $filters['source_id']);
        }

        foreach ($leadsQ->get() as $lead) {
            $results->push([
                'type'  => 'lead',
                'id'    => $lead->id,
                'name'  => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
            ]);
        }

        // Customers
        if (!empty($filters['include_customers'])) {
            $custQ = Customer::query();
            if (!empty($filters['customer_status'])) {
                $custQ->where('status', $filters['customer_status']);
            }
            foreach ($custQ->get() as $customer) {
                $results->push([
                    'type'  => 'customer',
                    'id'    => $customer->id,
                    'name'  => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ]);
            }
        }

        return $results;
    }

    public function syncMemberCount(): void
    {
        $count = $this->type === 'static'
            ? $this->members()->count()
            : $this->resolveMembers()->count();

        $this->update(['member_count' => $count]);
    }
}
