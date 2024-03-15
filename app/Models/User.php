<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Followings\Following;
use App\Models\Library\Library;
use App\Models\Surveys\Question;
use App\Models\Exams\ExamFinalMark;
use App\Models\Surveys\UserAnswerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'points' => 'integer'
    ];

    public function blogComments()
    {
        return $this->hasMany(BlogComment::class);
    }


    public function userAnswerStatus()
    {
        return $this->hasMany(UserAnswerStatus::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(ServiceSubscription::class);
    }


    public function blogs()
    {
        return $this->hasMany(Blog::class)->where('status', 'active');
    }
    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }
    public function libraries()
    {
        return $this->hasMany(Library::class)->where('status', 'active');
    }
    public function surveys()
    {
        return $this->hasMany(Question::class)->where('status', 'active');
    }
    public function competitionPrizes()
    {
        return $this->hasManyThrough(CompetitionAnswerPrize::class, CompetitionAnswer::class);
    }
    public function followings()
    {
        return $this->hasMany(Following::class);
    }
    public function getImageAttribute(){
        return asset('public/uploads/user-image/'.$this->attributes['image']);
    }
    
    public function exams(){
        return $this->hasMany(ExamFinalMark::class)->where('normal_mark', '>=',10);

    }
    public function badges()
    {
        return $this->hasMany(UserBadge::class);
    }
    

}
