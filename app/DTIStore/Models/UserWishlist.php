<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserWishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_variation_id',
        'deleted_at'
    ];
}
