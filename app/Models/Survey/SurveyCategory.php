<?php

namespace App\Models\Survey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'category_id' => 'integer',
    ];
}
