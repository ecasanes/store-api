<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'activity_log_type_id',
        'user_id',
        'user_firstname',
        'user_lastname',
        'user_email',
        'user_phone',
        'branch_id',
        'role_name',
        'transaction_type',
        'transaction_status',
        'transaction_type_id',
        'transaction_type_name',
        'readable_message',
        'transaction_id',
        'status',
        'deleted_at'
    ];
}
