<?php

namespace App\Models\Exams;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamFinalMark extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    protected $casts = [
        'normal_mark' => 'float',
        'canady_mark' => 'float',
        'canady2_mark' => 'float',
    ];
}
