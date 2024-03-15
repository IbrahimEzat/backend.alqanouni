<?php

namespace App\Models;

use App\Models\User;
use App\Models\BlogView;
use App\Models\BlogPoint;
use App\Models\BlogComment;
use App\Models\BlogCommentCount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Blog extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function blogPoints()
    {
        return $this->hasOne(BlogPoint::class);
    }

    public function blogWishListCounts(){
        return $this->hasOne(BlogWishListCount::class);
    }
    public function blogViews()
    {
        return $this->hasOne(BlogView::class);
    }
    public function blogCommentCounts()
    {
        return $this->hasOne(BlogCommentCount::class);
    }
    public function blogComments()
    {
        return $this->hasMany(BlogComment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function topics()
    {
        return $this->morphMany(TopicSection::class, 'section');
    }
    public function getImageAttribute()
    {
        return asset('public/uploads/blogs/' . $this->attributes['image']);
    }
}
