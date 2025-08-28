<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id', 
        'reset_by_admin_id',
        'reset_time',
        'reason'
    ];

    protected $casts = [
        'reset_time' => 'datetime'
    ];

    public $timestamps = false; // Using reset_time instead

    /**
     * Get the user that was reset
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject that was reset
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the admin who performed the reset
     */
    public function resetByAdmin()
    {
        return $this->belongsTo(User::class, 'reset_by_admin_id');
    }
}