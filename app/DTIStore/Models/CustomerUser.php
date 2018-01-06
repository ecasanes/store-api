<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $fillable = [
        'customer_id',
        'branch_id',
        'user_id',
        'status',
        'deleted_at'
    ];
}
