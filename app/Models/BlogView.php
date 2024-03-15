<?php

namespace App\Models;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogView extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
