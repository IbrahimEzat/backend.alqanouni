<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    use GeneralTrait;
    public function getProfileInfo(Request $request)
    {
        $user = User::where('id', $request->user_id)->select(['id', 'name', 'job', 'about_me', 'image', 'points'])
            ->withCount(['blogs', 'discussions', 'libraries', 'surveys', 'subscriptions', 'competitionPrizes', 'exams'])->first();
        return $this->mainResponse(true, 'true', $user, []);
    }
}
