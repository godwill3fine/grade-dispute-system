<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'dispute_id',
        'file_path',
        'uploaded_by',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class, 'dispute_id');
    }
}
