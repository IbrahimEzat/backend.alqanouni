<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(ServiceImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ServiceReview::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(ServiceSubscription::class);
    }

    public function getCoverAttribute()
    {
        return asset('public/uploads/services/covers/'.$this->attributes['cover']);

    }
}
