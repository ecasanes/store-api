<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'name',
        'code',
        'start',
        'end',
        'discount_type',
        'discount',
        'status',
        'deleted_at'
    ];
}
