<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'answers_json',
        'current_question_idx',
        'original_start_time',
        'last_activity_at',
        'is_completed',
    ];

    protected $casts = [
        'answers_json' => 'array', // Automatically cast JSON to array and vice-versa
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
