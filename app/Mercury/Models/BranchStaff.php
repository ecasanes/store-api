<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BranchStaff extends Model
{
    protected $fillable = [
        'staff_id',
        'branch_id',
        'user_id',
        'can_void',
        'has_multiple_access',
        'status',
        'deleted_at'
    ];
}
