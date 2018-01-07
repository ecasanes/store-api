<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [

        'transaction_id',

        'product_variation_id',
        'quantity',
        'product_name',
        'selling_price',
        'shipping_price',

        'buyer_status',
        'seller_status',

        'deleted_at'

    ];
}
