<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PersonalToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait AuthTrait
{

    use GeneralTrait;

    // public function generalCheckVerifyCode(Request $request, $type)
    // {
    //     $Validator = Validator::make($request->all(), [
    //         'email' => ['email', 'required', 'exists:verification_codes,email'],
    //         'code' => ['required'],
    //     ]);
    //     if ($Validator->fails()) {
    //         return $this->returnValidationError('validation error occur', $Validator->errors(), 422);
    //     }
    //     $res = verificationCode::where('email', $request->email)->where('type', $type)->first();
    //     if ($res) {
    //         if (Hash::check($request->code, $res->code))
    //             return $this->returnSuccessMessage('correct code');
    //     }
    //     return $this->returnError(429, 'inCorrect code');
    // }

    // public function generalSendVerifyEmail(Request $request, $validate, $type)
    // {
    //     $Validator = Validator::make($request->all(), $validate);
    //     if ($Validator->fails()) {
    //         return $this->returnValidationError('validation error occur', $Validator->errors(), 422);
    //     }
    //     return $this->sendCode($request, $type);
    // }

    // public function sendCode(Request $request, $type)
    // {
    //     $code = $this->creatCode();
    //     $data = ['subject' => 'verfiy email', 'view' => 'verfiyEmail', 'data' => ['code' =>  $code]];
    //     $res = $this->sendEmail($request, $data);
    //     if ($res->getData()->status) {
    //         $gUser = verificationCode::where('email', $request->email)->where('type', $type)->first();
    //         if (!$gUser) {
    //             verificationCode::create([
    //                 'email' => $request->email,
    //                 'code' => Hash::make($code),
    //                 'type' => $type
    //             ]);
    //         } else {
    //             $gUser->update(['code' => Hash::make($code)]);
    //         }
    //     }
    //     return $this->returnSuccessMessage("we send email");
    // }

    protected function generateToken($id)
    {
        $token = fake()->uuid();
        $hashToken = Hash::make($token);
        $tokenInfo = PersonalToken::create([
            'user_id' => $id,
            'token' => $hashToken
        ]);
        $tokenInfo['returnedToken'] = $token;
        return $tokenInfo;
    }
}
