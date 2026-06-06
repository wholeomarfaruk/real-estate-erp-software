<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    protected $table = 'expense_types';

    protected $fillable = [
        'key',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
