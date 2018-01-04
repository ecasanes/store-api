<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLogType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'deleted_at',
        'is_transaction'
    ];
}
