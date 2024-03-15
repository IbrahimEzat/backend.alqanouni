<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionWishlist extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'competition_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function competition()
    {
        return $this->hasMany(Competition::class);
    }

    public function user()
    {
        return $this->hasMany(User::class);
    }

}
