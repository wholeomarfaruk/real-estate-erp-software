<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'kind', 'name', 'organization', 'designation', 'phone', 'phone_alt', 'email', 'gender',
        'date_of_birth', 'doc_type', 'doc_no', 'tin_no', 'address', 'district', 'user_id', 'status', 'notes', 'created_by',
    ];

    /** Mirror the DB defaults so new instances are complete before a refresh. */
    protected $attributes = [
        'kind'   => 'individual',
        'status' => 'active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (MasterProfile $profile): void {
            if (! $profile->code) {
                $next = (int) static::withTrashed()->max('id') + 1;
                $profile->code = 'MP-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function roles(): HasMany
    {
        return $this->hasMany(MasterProfileRole::class);
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(ProfileType::class, 'master_profile_roles')->withPivot(['id', 'status', 'reference']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2)))) ?: '?';
    }
}
