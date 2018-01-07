<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'size',
        'metrics',
        'cost_price',
        'selling_price',
        'shipping_price',
        'franchisee_price',
        'status',
        'deleted_at',
        'product_id'
    ];

    protected $hidden = [
        'quantity'
    ];
}
