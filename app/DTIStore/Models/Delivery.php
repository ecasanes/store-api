<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'branch_id',
        'status',
        'delivery_date',
        'remarks',
        'invoice_no',
        'deleted_at'
    ];
}
