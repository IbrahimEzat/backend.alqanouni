<?php

namespace App\Models\Exams;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'exam_answers';
    protected $casts = ['isCorrect'=>'boolean'];
}
