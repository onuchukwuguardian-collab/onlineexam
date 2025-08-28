<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes'; // Explicitly define table name

    protected $fillable = [
        'name',
        'level_group',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }
}
