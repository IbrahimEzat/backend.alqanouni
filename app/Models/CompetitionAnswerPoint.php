<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionAnswerPoint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'competition_answer_id' => 'integer',
        'answer_points' => 'integer'
    ];

    public function competitionAnswer()
    {
        return $this->belongsTo(CompetitionAnswer::class);
    }

}
