<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileItem extends Model
{
    protected $table = 'file_items';

    protected $fillable = [
        'file_id',
        'type',
        'size',
        'path',
    ];

}
