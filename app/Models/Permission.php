<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'read',
        'write',
        'changeName',
        'addUsers',
        'removeUsers',
        'changeStatus',
        'createPermissions',
        'changePermissions',
    ];
}
