<?php

namespace App\Models;

use App\Models\Library\Library;
use App\Models\Surveys\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Exams\Exam;

class Category extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_categories')->where('status','active');
    }
    public function discussions()
    {
        return $this->belongsToMany(Discussion::class, 'category_discussions');
    }
    public function surveys()
    {
        return $this->belongsToMany(Question::class, 'survey_categories', 'category_id', 'survey_question_id')->where('status', 'active');
    }
    public function libraries()
    {
        return $this->belongsToMany(Library::class, 'library_categories')->where('status', 'active');
    }
    
    public function competitions()
    {
        return $this->belongsToMany(Competition::class, 'competition_categories');
    }
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_categories')->where('status', 'active');
    }

    // public function files()
    // {
    //     return $this->belongsToMany(Library::class, 'library_categories');
    // }
}
