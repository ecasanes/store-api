<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'deleted_at'
    ];
}
