<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

class generalPageController extends Controller
{
    use GeneralTrait;
    public function arrangementUsers(Request $request){
        $users = User::all();
        if($users)
            return $this->mainResponse(true,'arrangement users',$users,[]);
        return $this->mainResponse(false,'حدث خطا',[],[]);
    }
}
