<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogWishListCount extends Model
{
    use HasFactory;
    protected $casts = [
        'count' => 'integer',
    ];
    protected $guarded = [];
    
}
