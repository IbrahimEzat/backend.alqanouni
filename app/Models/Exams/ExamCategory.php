<?php

namespace App\Models\Exams;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'category_id' => 'integer',
    ];
}
