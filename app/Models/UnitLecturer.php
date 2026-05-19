<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitLecturer extends Model
{
    protected $fillable = ['unit_code','lecturer_id','course'];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code');
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function courseRecord()
    {
        return $this->belongsTo(Course::class, 'course', 'code');
    }
}
