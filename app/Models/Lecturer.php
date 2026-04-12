<?php

namespace App\Models;

use App\Models\Concerns\HasSplitName;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasSplitName;

    protected $primaryKey = 'lecturer_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['lecturer_id', 'first_name', 'last_name', 'full_name', 'department'];

    public function units()
    {
        return $this->hasMany(UnitLecturer::class, 'lecturer_id');
    }
}
