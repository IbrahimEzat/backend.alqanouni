<?php

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryDownloadCount extends Model
{
    use HasFactory;
    protected $casts = [
        'download_count' => 'integer',
    ];
    protected $guarded = [];

}
