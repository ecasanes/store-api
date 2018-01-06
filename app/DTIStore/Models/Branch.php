<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'key',
        'name',
        'address',
        'city',
        'zip',
        'province',
        'phone',
        'status',
        'type',
        'company_id',
        'override_default_store_time',
        'default_start_time',
        'default_end_time',
        'deleted_at'
    ];
}