<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'question_id', 'selected_option_letter', 'is_correct'];
    // public $timestamps = false; // if you don't need created_at/updated_at here

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
