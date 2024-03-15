<?php

namespace App\Http\Middleware;

use App\Models\PersonalToken;
use App\Models\User;
use App\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminMiddleware
{
    use GeneralTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if($request->has('token') && $request->token) {
            $tokenId = null;
            $tokenUserId = null;
            $tokenReturned = null;
            if(is_array($request->token)) {
                $tokenId = $request->token['id'];
                $tokenUserId = $request->token['user_id'];
                $tokenReturned = $request->token['returnedToken'];
            } else {
                $tkn = json_decode($request->token);

                $tokenId = $tkn->{'id'};
                $tokenUserId = $tkn->{'user_id'};
                $tokenReturned = $tkn->{'returnedToken'};
            }

            //get token info from db
            $query = PersonalToken::find($tokenId);
            if($query) {
                //check user_id and user_type from db and request
                $dbUserId = $query->user_id;
                $requestUser = User::find($tokenUserId);

                if($requestUser && $dbUserId == $requestUser->id && $requestUser->type == 'admin'){
                    //check token
                    if (Hash::check($tokenReturned, $query->token)) {
                        return $next($request);
                    }
                } else {
                    return $this->mainResponse(false, 'Forbidden|Is not allowed to you access here', [], [], 403);
                }
            } else {
                return $this->mainResponse(false, 'Forbidden|Is not allowed to you access here', [], [], 402);
            }
        }
        return $this->mainResponse(false, 'Forbidden|Is not allowed to you access here', [], [], 402);

    }
}
