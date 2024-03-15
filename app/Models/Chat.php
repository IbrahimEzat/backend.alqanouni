<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function messages(){
        return $this->hasMany(ChatMessage::class);
    }
    public function lastChatMessage(){
        return $this->hasOne(ChatLastMessage::class);
    }
    public function user1(){
        return $this->hasOne(User::class,'id','user_id1');
    }
    public function user2(){
        return $this->hasOne(User::class,'id','user_id2');
    }
}
