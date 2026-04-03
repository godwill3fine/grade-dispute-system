<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $primaryKey = 'unit_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['unit_code', 'unit_name', 'department_id'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    
    public function lecturers()
    {
        return $this->belongsToMany(
            Lecturer::class,
            'unit_lecturers',
            'unit_code',
            'lecturer_id',
            'unit_code',
            'lecturer_id'
        );
    }

    public function lecturerMappings()
    {
        return $this->hasMany(UnitLecturer::class, 'unit_code', 'unit_code');
    }
    
    public function disputes()
    {
        return $this->hasMany(Dispute::class, 'unit_code', 'unit_code');
    }
}
