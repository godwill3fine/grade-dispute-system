<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeLog extends Model
{
    protected $fillable = ['dispute_id','action','performed_by'];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }
}
