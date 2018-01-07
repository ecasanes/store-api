<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserCart extends Model
{
    protected $fillable = [
        'user_id',
        'product_variation_id',
        'cart_quantity',
        'deleted_at'
    ];
}
