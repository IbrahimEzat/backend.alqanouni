<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'degree' => 'float',
        'competition_id' => 'integer',
        'user_id' => 'integer'
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitionAnswerPoint()
    {
        return $this->hasOne(CompetitionAnswerPoint::class);
    }

    public function competitionAnswerPrize()
    {
        return $this->hasOne(CompetitionAnswerPrize::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
