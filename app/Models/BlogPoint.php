<?php

namespace App\Models;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogPoint extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'blog_points' => 'integer',
    ];
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
