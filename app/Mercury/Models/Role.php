<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'code',
        'rank',
        'status',
        'has_permissions',
        'deleted_at'
    ];
}
