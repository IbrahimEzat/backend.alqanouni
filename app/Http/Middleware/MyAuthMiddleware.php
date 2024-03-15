<?php

namespace App\Http\Middleware;

use App\Models\PersonalToken;
use App\Models\User;
use App\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MyAuthMiddleware
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
            if($tokenUserId == -1)
                return $this->mainResponse(false, 'Not Auth', [], [], 402);

            $query = PersonalToken::find($tokenId);
            if($query) {

                //check user_id from db and request
                $dbUserId = $query->user_id;

                if($dbUserId == $tokenUserId) {
                    //check token
                    if (Hash::check($tokenReturned, $query->token)) {
                        return $next($request);
                    }
                }
                return $this->mainResponse(false, 'Not Auth', [], [], 402);
            } else {
                return $this->mainResponse(false, 'Check your token info', [], [], 402);
            }

        }
    }
}
