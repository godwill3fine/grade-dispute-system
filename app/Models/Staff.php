<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'staff_id',
        'full_name',
        'role',
        'department',
    ];
}
