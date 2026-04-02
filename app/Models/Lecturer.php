<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    protected $primaryKey = 'lecturer_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['lecturer_id','full_name','department'];

    public function units()
    {
        return $this->hasMany(UnitLecturer::class, 'lecturer_id');
    }
}
