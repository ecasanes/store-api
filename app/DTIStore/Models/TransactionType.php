<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'group',
        'description',
        'status',
        'deleted_at'
    ];
}
