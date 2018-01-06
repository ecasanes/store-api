<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [

        'transaction_id',

        'product_variation_id',
        'product_name',
        'product_size',
        'product_metrics',
        'product_cost_price',
        'product_selling_price',
        'product_franchisee_price',

        'shortover_product_variation_id',
        'shortover_product_name',
        'shortover_product_size',
        'shortover_product_metrics',
        'shortover_product_cost_price',
        'shortover_product_selling_price',

        'product_discount',

        'quantity',
        'current_quantity',
        'current_warehouse_quantity',
        'remaining_quantity',
        'remaining_warehouse_quantity',

        'status',
        'deleted_at'

    ];
}
