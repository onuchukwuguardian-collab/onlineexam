<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Or your API auth

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'class_id',
        'registration_number',
        'unique_id', // School Passcode
        'email_verified_at',
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'unique_id', // Hide passcode from JSON responses
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'unique_id' => 'hashed', // Hash the passcode
    ];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Alias for easier access
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function userScores()
    {
        return $this->hasMany(UserScore::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStudent(): bool
    {
        return in_array($this->role, ['user', 'student']);
    }

    /**
     * Generate a secure unique passcode for the student
     */
    public static function generateSecurePasscode($classPrefix = null): string
    {
        $prefix = $classPrefix ?? 'STU';
        $randomPart = strtoupper(\Illuminate\Support\Str::random(6));
        $timestamp = substr(time(), -4); // Last 4 digits of timestamp
        
        return $prefix . $randomPart . $timestamp;
    }

    /**
     * Set the unique_id attribute (automatically hashes it)
     */
    public function setUniqueIdAttribute($value)
    {
        // Only hash if it's not already hashed
        if ($value && !str_starts_with($value, '$2y$')) {
            $this->attributes['unique_id'] = \Illuminate\Support\Facades\Hash::make($value);
        } else {
            $this->attributes['unique_id'] = $value;
        }
    }
}
