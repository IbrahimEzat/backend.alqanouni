<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionView extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'competition_id' => 'integer',
        'view_count' => 'integer'
    ];

    public function competition()
    {
        return $this->hasOne(Competition::class);
    }
}
