<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'image_url',
        'product_category_id',
        'product_condition_id',
        'status',
        'deleted_at'
    ];
}
