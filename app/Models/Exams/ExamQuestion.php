<?php

namespace App\Models\Exams;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function answer()
    {
        return $this->hasMany(Answer::class);
    }
}
