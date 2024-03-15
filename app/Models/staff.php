<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class staff extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getImageAttribute(){
        return asset('public/uploads/user-image/'.$this->attributes['image']);
    }
}
