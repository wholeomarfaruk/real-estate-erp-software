<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfileType extends Model
{
    /** Modules a profile type can link to: key => [label, model class, route name for show]. */
    public const MODULES = [
        'customer' => ['label' => 'Customers', 'model' => Customer::class, 'route' => 'admin.crm.customers.show'],
        'supplier' => ['label' => 'Suppliers', 'model' => Supplier::class, 'route' => 'admin.supplier.suppliers.view'],
        'employee' => ['label' => 'Employees', 'model' => Employee::class, 'route' => null],
    ];

    public const ICONS = [
        'user' => 'User', 'users' => 'Users', 'truck' => 'Truck', 'badge' => 'Badge', 'bank' => 'Bank',
        'crown' => 'Crown', 'map' => 'Map', 'briefcase' => 'Briefcase', 'handshake' => 'Handshake',
        'building' => 'Building', 'shield' => 'Shield', 'star' => 'Star',
    ];

    public const TONES = ['slate', 'emerald', 'amber', 'sky', 'violet', 'rose', 'teal', 'indigo', 'orange'];

    public const FIELD_TYPES = ['text' => 'Text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Dropdown', 'textarea' => 'Long text'];

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'tone', 'module', 'login_role', 'fields', 'is_system', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'fields'    => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(MasterProfileRole::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Tailwind classes for a tone: soft badge, solid dot/bar, icon tile and ring. */
    public static function toneClasses(?string $tone): array
    {
        return match ($tone) {
            'emerald' => ['badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'dot' => 'bg-emerald-500', 'tile' => 'bg-emerald-50 text-emerald-600', 'bar' => 'from-emerald-500 to-emerald-400'],
            'amber'   => ['badge' => 'bg-amber-50 text-amber-700 ring-amber-600/20',       'dot' => 'bg-amber-500',   'tile' => 'bg-amber-50 text-amber-600',     'bar' => 'from-amber-500 to-amber-400'],
            'sky'     => ['badge' => 'bg-sky-50 text-sky-700 ring-sky-600/20',             'dot' => 'bg-sky-500',     'tile' => 'bg-sky-50 text-sky-600',         'bar' => 'from-sky-500 to-sky-400'],
            'violet'  => ['badge' => 'bg-violet-50 text-violet-700 ring-violet-600/20',    'dot' => 'bg-violet-500',  'tile' => 'bg-violet-50 text-violet-600',   'bar' => 'from-violet-500 to-violet-400'],
            'rose'    => ['badge' => 'bg-rose-50 text-rose-700 ring-rose-600/20',          'dot' => 'bg-rose-500',    'tile' => 'bg-rose-50 text-rose-600',       'bar' => 'from-rose-500 to-rose-400'],
            'teal'    => ['badge' => 'bg-teal-50 text-teal-700 ring-teal-600/20',          'dot' => 'bg-teal-500',    'tile' => 'bg-teal-50 text-teal-600',       'bar' => 'from-teal-500 to-teal-400'],
            'indigo'  => ['badge' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',    'dot' => 'bg-indigo-500',  'tile' => 'bg-indigo-50 text-indigo-600',   'bar' => 'from-indigo-500 to-indigo-400'],
            'orange'  => ['badge' => 'bg-orange-50 text-orange-700 ring-orange-600/20',    'dot' => 'bg-orange-500',  'tile' => 'bg-orange-50 text-orange-600',   'bar' => 'from-orange-500 to-orange-400'],
            default   => ['badge' => 'bg-slate-100 text-slate-700 ring-slate-600/20',      'dot' => 'bg-slate-500',   'tile' => 'bg-slate-100 text-slate-600',    'bar' => 'from-slate-500 to-slate-400'],
        };
    }

    public function tones(): array
    {
        return self::toneClasses($this->tone);
    }

    public function moduleConfig(): ?array
    {
        return $this->module ? (self::MODULES[$this->module] ?? null) : null;
    }
}
