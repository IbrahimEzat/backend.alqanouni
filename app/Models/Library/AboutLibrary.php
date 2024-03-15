<?php

namespace App\Models\Library;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutLibrary extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function getAuthorImageAttribute()
    {
        return asset('public/uploads/library/auther_images/' . $this->attributes['author_image']);
    }
    public function getFileAttribute()
    {
        return asset('public/uploads/library/files/' . $this->attributes['file']);
    }
}
