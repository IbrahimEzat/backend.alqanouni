<?php

namespace App\Models\Followings;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Library\LibraryCommentCount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Following extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'following');
    }
    public function followingCount()
    {
        return $this->hasOne(LibraryCommentCount::class);
    }
}
