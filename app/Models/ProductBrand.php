<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_id',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductBrand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function image()
    {
        return $this->belongsTo(File::class, 'image_id');
    }
}
