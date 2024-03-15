<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Topic extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function topic()
    {
        return $this->morphTo();
    }

    public function getImageAttribute()
    {
        return asset('public/uploads/topics/images/' . $this->attributes['image']);
    }

    // public function getTypeAttribute()
    // {
    //     if($this->attributes['type'] == 'blog')
    //         return 'المقالات';
    //     else if ($this->attributes['type'] == 'disscusion')
    //         return 'المناقشات';
    //     else if ($this->attributes['type'] == 'library')
    //         return 'مكتبة';
    //     else if ($this->attributes['type'] == 'survey')
    //         return 'استطلاعات';
    //     else if ($this->attributes['type'] == 'competition')
    //         return 'مسابقات';
    //     else if ($this->attributes['type'] == 'exam')
    //         return 'اختبارات';
    //     else
    //         return 'استشارات';
    // }
}
