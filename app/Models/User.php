<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'phone',
        'country_code',
        'address',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
        ];
    }
    // User has many panels
    public function panels()
    {
        return $this->belongsToMany(Panel::class);
    }

    public function hasPanel(string $panelSlug): bool
    {
        return $this->panels()->where('slug', $panelSlug)->exists();
    }
    //roleName
    public function roleName(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function managedStores(): HasMany
    {
        return $this->hasMany(Store::class, 'manager_user_id');
    }

    public function assignedStores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    public function canAccessStore(int $storeId): bool
    {
        if ($this->hasRole('superadmin') || $this->can('inventory.stock.report.view')) {
            return true;
        }

        return $this->managedStores()->whereKey($storeId)->exists()
            || $this->assignedStores()->where('stores.id', $storeId)->exists();
    }

}
