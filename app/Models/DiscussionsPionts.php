<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionsPionts extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'count_points' => 'integer',
    ];
    public function discussion()
    {
        return $this->belongsTo(Blog::class);
    }

}
