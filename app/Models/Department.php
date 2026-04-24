<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class)->orderBy('name');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class)->orderBy('name');
    }
}

