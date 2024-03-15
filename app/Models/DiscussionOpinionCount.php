<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionOpinionCount extends Model
{
    use HasFactory;
    protected $casts = [
        'count_opinions' => 'integer',
    ];
    protected $guarded = [];
}
