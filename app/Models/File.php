<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'files';
    protected $fillable = [
        'name',
        'caption',
        'type',
        'extension',
    ];

    public function items()
    {
        return $this->hasMany(FileItem::class);
    }
}
