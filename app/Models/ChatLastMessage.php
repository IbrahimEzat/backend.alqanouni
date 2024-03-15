<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLastMessage extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'is_read' => 'boolean',
    ];
    public function message(){
        return $this->hasOne(ChatMessage::class,'id','last_message_id');
    }
}
