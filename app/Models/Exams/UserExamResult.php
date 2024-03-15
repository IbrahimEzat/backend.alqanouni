<?php

namespace App\Models\Exams;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserExamResult extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
