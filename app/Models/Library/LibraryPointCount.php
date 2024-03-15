<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryPointCount extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $casts = [
        'point_count' => 'integer',
    ];

}
