<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class Competition extends Model
{
    use HasFactory;
    protected $guarded = [];
     protected $casts = [
         'is_correct' => 'boolean',
     ];

    public function competitionView()
    {
        return $this->hasOne(CompetitionView::class);
    }

    public function wishlists()
    {
        return $this->hasMany(CompetitionWishlist::class);
    }

    public function categories()
    {
        return $this->hasMany(CompetitionCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function competitionAnswers()
    {
        return $this->hasMany(CompetitionAnswer::class);
    }

    public function getPrizeImageAttribute()
    {
        return asset('public/uploads/competitions/prizes/'.$this->attributes['prize_image']);
    }

    public function getSponsorImageAttribute()
    {
        return $this->attributes['sponsor_image'] ? asset('public/uploads/competitions/sponsors/'.$this->attributes['sponsor_image']) : '';
    }

    public function getStatusAttribute()
    {
        $from = now();
        $to = new Carbon($this->attributes['duration']);
        return $from->diffInDays($to, false) + 1;
    }
}
