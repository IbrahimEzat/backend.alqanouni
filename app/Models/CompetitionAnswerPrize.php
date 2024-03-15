<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionAnswerPrize extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'competition_id' => 'integer',
        'competition_answer_id' => 'integer',
    ];
    public function competitionAnswer()
    {
        return $this->belongsTo(CompetitionAnswer::class);
    }
}
