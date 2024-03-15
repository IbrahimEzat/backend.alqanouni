<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionUserVoitCount extends Model
{
    use HasFactory;
    protected $casts = [
        'number_user_voit' => 'integer',
    ];
    protected $guarded = [];
}
