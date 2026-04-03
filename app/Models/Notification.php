<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'title',
        'dispute_id',
        'recipient_id',
        'link',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id', 'identifier');
    }

    // Backward-compatible alias.
    public function user()
    {
        return $this->recipient();
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute::class, 'dispute_id');
    }
    
}
