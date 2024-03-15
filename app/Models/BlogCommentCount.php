<?php

namespace App\Models;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogCommentCount extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $table = 'blog_comments_count';
    
    protected $casts = [
        'blog_comments' => 'integer',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
