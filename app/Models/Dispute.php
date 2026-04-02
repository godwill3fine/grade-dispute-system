<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'unit_code',
        'assigned_to',
        'assigned_role',
        'title',
        'description',
        'status'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code');
    }

    public function logs()
    {
        return $this->hasMany(DisputeLog::class);
    }
}
