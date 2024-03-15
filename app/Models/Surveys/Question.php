<?php

namespace App\Models\Surveys;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsToMany(Category::class);
    }

    public function questionPoints()
    {
        return $this->hasOne(QuestionPoint::class);
    }

    public function questionUserVoitCount(){
        return $this->hasOne(QuestionUserVoitCount::class);

    }

    public function questionViews()
    {
        return $this->hasOne(QuestionView::class);
    }
    public function questionWishList()
    {
        return $this->hasMany(QuestionWishlist::class);
    }
    public function questionWishListCount()
    {
        return $this->hasOne(QuestionWishlistCount::class);
    }
    public function surveyAnswers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }
    public function userAnswerStatus()
    {
        return $this->hasOne(UserAnswerStatus::class);
    }
    public function usersurveyAnswers()
    {
        return $this->hasMany(UserSurveyAnswer::class);
    }

}
