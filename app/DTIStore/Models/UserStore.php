<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserStore extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'deleted_at'
    ];
}
