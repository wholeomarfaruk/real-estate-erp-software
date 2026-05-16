<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UnitType extends Model
{
    protected $fillable = ['name', 'slug'];

    public static function makeSlug(string $name): string
    {
        return Str::slug($name);
    }
}
