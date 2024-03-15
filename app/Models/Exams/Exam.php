<?php

namespace App\Models\Exams;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function examQuestion()
    {
        return $this->hasMany(ExamQuestion::class);
    }
    protected $casts = ['participants'=>'integer' , 'duration' => 'integer'];
}
