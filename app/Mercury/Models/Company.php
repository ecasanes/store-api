<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'status',
        'default_metrics',
        'default_start_time',
        'default_end_time',
        'default_color',
        'default_vat',
        'default_low_inventory_threshold',
        'deleted_at'
    ];
}