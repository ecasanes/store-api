<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_no',
        'store_id',
        'total',
        'buyer_status',
        'seller_status',
        'deleted_at'
    ];
}
