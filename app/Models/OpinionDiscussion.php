<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpinionDiscussion extends Model
{
    use HasFactory;

    protected $guarded =[];
    public function discussion()
    {
        return $this->belongsTo(Blog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function opinionPoints(){
        return $this->hasOne(OpinionDiscussionPoints::class);
    }

    public function comments(){
        return $this->hasMany(CommentOpinionDiscussion::class);
    }

}
