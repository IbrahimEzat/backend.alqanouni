<?php

namespace App\Models\Followings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowingCount extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'following_count' => 'integer',
    ];
}
