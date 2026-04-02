<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $primaryKey = 'unit_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['unit_code','unit_name'];

    public function lecturers()
    {
        return $this->hasMany(UnitLecturer::class, 'unit_code');
    }
}
