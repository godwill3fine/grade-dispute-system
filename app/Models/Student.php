<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'reg_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['reg_number','full_name','course','year'];

    public function disputes()
    {
        return $this->hasMany(Dispute::class, 'student_id');
    }
}
