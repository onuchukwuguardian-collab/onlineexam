<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserScore extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'subject_id',
        'score',
        'total_questions',
        'time_taken_seconds',
        'submission_time',
        'percentage'
    ];

    protected $casts = [
        'submission_time' => 'datetime'
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
