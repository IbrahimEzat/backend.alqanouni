<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges');
    }

    public function getImageAttribute()
    {
        return asset('public/uploads/badges/'.$this->attributes['image']);
    }
}
