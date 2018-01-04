<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BranchStock extends Model
{
    protected $fillable = [
        'quantity',
        'branch_id',
        'current_delivery_quantity',
        'product_variation_id',
        'deleted_at'
    ];
}
