<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    // These temporary values let the creator customize the first audit log entry.
    protected ?string $creationPerformedBy = null;

    protected ?string $creationLogAction = null;

    protected ?string $creationLogComment = null;

    protected $fillable = [
        'ticket_number',
        'student_id',
        'type',
        'unit_code',
        'assignment_name',
        'priority',
        'assigned_to',
        'assigned_role',
        'title',
        'description',
        'status',
        'escalation_level',
        'escalation_reason',
        'escalated_at',
        'resolved_at',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'reg_number');
    }
    
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_code', 'unit_code');
    }
    
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'identifier');
    }
    
    public function logs()
    {
        return $this->hasMany(DisputeLog::class, 'dispute_id');
    }
    
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'dispute_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'dispute_id');
    }

    public function useCreationLogContext(?string $performedBy, ?string $action = null, ?string $comment = null): static
    {
        // Store creation log metadata until the model is saved.
        $this->creationPerformedBy = is_string($performedBy) ? trim($performedBy) : null;
        $this->creationLogAction = is_string($action) ? trim($action) : null;
        $this->creationLogComment = is_string($comment) ? trim($comment) : null;

        return $this;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispute) {
            // Generate readable ticket numbers when the caller has not supplied one.
            if (! empty($dispute->ticket_number)) {
                return;
            }

            $last = Dispute::whereNotNull('ticket_number')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($last && is_string($last->ticket_number) && str_starts_with($last->ticket_number, 'DSP-')) {
                $lastNumber = (int) substr($last->ticket_number, 4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $dispute->ticket_number = 'DSP-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        });
        static::created(function ($dispute) {
            // Every dispute starts with a timeline entry for auditability.
            DisputeLog::create([
                'dispute_id' => $dispute->id,
                'action' => $dispute->creationLogAction ?: 'Dispute Submitted',
                'old_status' => null,
                'new_status' => $dispute->status,
                'comment' => $dispute->creationLogComment ?: 'Dispute created by student',
                'performed_by' => $dispute->creationPerformedBy ?: $dispute->student_id,
            ]);
        });
    }
}
