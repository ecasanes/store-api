<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'buyer_user_id',
        'voucher_id',
        'payment_mode_id',
        'voucher_code',
        'discount_type',
        'discount',
        'address',
        'total',
        'status',
        'deleted_at'
    ];
}
