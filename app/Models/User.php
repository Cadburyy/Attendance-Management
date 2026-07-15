<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'salt',
        'role',
        'picture',
        'picture_hash',
        'face_embedding',
        'otp_code',
        'otp_expires_at'
    ];

    protected $hidden = [
        'password', 
        'remember_token', 
        'salt'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'face_embedding' => 'array',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function faceReferences()
    {
        return $this->hasMany(\App\Models\FaceReference::class);
    }
}