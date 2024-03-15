<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionViews extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function discussion()
    {
        return $this->belongsTo(Blog::class);
    }
}
