<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function discussionPoints()
    {
        return $this->hasOne(DiscussionsPionts::class);
    }
    public function discussionViews()
    {
        return $this->hasOne(DiscussionViews::class);
    }
    public function discussionStars()
    {
        return $this->hasOne(DiscussionStars::class);
    }

    public function discussionOpinions()
    {
        return $this->hasMany(OpinionDiscussion::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function discussionOpinionCount()
    {
        return $this->hasOne(DiscussionOpinionCount::class);
    }
}
