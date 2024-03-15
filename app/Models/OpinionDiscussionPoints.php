<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpinionDiscussionPoints extends Model
{
    use HasFactory;
    protected $casts = [
        'count_points' => 'integer',
    ];
    protected $guarded =[];

}
