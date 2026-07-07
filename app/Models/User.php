<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user) {
            if ($user->isDirty('password')) {
                $user->requires_password_change = false;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_enabled' => 'boolean',
            'fingerprints' => 'array',
            'face_templates' => 'array',
            'requires_password_change' => 'boolean',
        ];
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'pin', 'pin');
    }

    public function getPrivilegeLabelAttribute(): string
    {
        return match ((int) $this->privilege) {
            0 => 'User',
            14 => 'Admin',
            default => 'Unknown',
        };
    }

    public function getHasLoginAttribute(): bool
    {
        return (int) $this->privilege === 14
            && filled($this->email)
            && filled($this->password);
    }
}
