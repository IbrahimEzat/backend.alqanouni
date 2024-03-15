<?php

namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionWishlistCount extends Model
{
    use HasFactory;
    protected $casts = [
        'question_wishlist_count' => 'integer',
    ];
    protected $guarded = [];
}
