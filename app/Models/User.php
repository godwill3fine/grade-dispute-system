<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'identifier',
        'role',
        'role_id',
        'department_id',
        'status',
        'last_login',
        'password',
    ];
    protected $hidden = ['password', 'remember_token'];
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Backward-compatible alias without clashing with the string "role" column.
    public function roleRelation()
    {
        return $this->roleRecord();
    }
    
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    
    public function studentDisputes()
    {
        return $this->hasMany(Dispute::class, 'student_id', 'identifier');
    }
    
    public function assignedDisputes()
    {
        return $this->hasMany(Dispute::class, 'assigned_to', 'identifier');
    }
    
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id', 'identifier');
    }
    
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }
}
