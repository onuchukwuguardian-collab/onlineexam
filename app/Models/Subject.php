<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $class_id
 * @property int $exam_duration_minutes
 * @property-read \App\Models\ClassModel $classModel
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Question[] $questions
 */
class Subject extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'class_id', 'exam_duration_minutes'];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
