<?php

namespace App\Models;

use App\Models\Concerns\HasSplitName;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasSplitName;

    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'staff_id',
        'first_name',
        'last_name',
        'full_name',
        'role',
        'department',
    ];
}
