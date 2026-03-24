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
        'encrypted_dek',
        'picture_iv',
        'dek_iv'
    ];

    protected $hidden = [
        'password', 
        'remember_token', 
        'salt',
        'encrypted_dek',
        'dek_iv'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(\App\Models\Attendance::class);
    }

    public function absences()
    {
        return $this->hasMany(\App\Models\Absence::class);
    }
}