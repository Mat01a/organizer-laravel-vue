<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class projects_users extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'permission_id',
    ];
}
