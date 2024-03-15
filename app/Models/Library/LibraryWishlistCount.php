<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryWishlistCount extends Model
{
    use HasFactory;

    protected $casts = [
        'wishlist_count' => 'integer',
    ];
    protected $guarded = [];

}
