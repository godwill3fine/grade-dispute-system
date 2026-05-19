<?php

namespace App\Models;

use App\Models\Concerns\HasSplitName;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasSplitName;

    protected $primaryKey = 'reg_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['reg_number', 'first_name', 'last_name', 'full_name', 'course', 'year', 'department', 'faculty'];

    public function disputes()
    {
        return $this->hasMany(Dispute::class, 'student_id');
    }

    public function courseRecord()
    {
        return $this->belongsTo(Course::class, 'course', 'code');
    }

    public function courseLabel(): string
    {
        return $this->courseRecord?->display_name
            ?? (Course::normalizeCode($this->course) ?: 'Not assigned');
    }
}
