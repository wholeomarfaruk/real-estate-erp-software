<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EngineerProject extends Model
{
    protected $table = 'engineer_projects';
    protected $fillable = [
        'user_id',
        'project_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
