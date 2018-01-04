<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    protected $fillable = [
        'product_variation_id',
        'delivery_id',
        'quantity',
        'status',
        'deleted_at'
    ];
}
