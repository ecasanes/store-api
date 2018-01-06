<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'product_variation_id',
        'discount',
        'discount_type',
        'code',
        'status',
        'apply_to',
        'status',
        'quantity',
        'amount',
        'deleted_at'
    ];
}
