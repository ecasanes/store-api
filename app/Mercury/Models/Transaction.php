<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'or_no',
        'invoice_no',
        'transaction_type_id',
        'staff_id',
        'customer_user_id',

        'user_id',
        'branch_id',
        'customer_id',
        'branch_type',
        'sub_type',

        'remarks',

        'customer_firstname',
        'customer_lastname',
        'customer_phone',

        'staff_firstname',
        'staff_lastname',
        'staff_phone',

        'discount',

        'price_rule_id',
        'price_rule_code',
        'price_rule_name',
        'discount_type',
        'discount_value',
        'discount_apply_to',
        'discount_product_variation_id',
        'discount_product_name',
        'discount_product_size',
        'discount_product_metrics',
        'discount_product_selling_price',
        'discount_product_cost_price',
        'discount_quantity',
        'discount_amount',
        'discount_remarks',

        'grand_total',

        'referenced_transaction_id',

        'status', // VOID, ETC

        'created_at',
        'deleted_at'


    ];
}
