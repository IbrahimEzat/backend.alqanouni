<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryCommentCount extends Model
{
    use HasFactory;

    protected $casts = [
        'comment_count' => 'integer',
    ];
    protected $guarded = [];

}
