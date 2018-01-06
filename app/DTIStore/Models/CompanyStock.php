<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyStock extends Model
{
    protected $fillable = [
        'quantity',
        'product_variation_id',
        'company_id',
        'deleted_at'
    ];
}
