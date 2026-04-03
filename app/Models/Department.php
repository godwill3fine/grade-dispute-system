<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'faculty'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'department_id');
    }
}
