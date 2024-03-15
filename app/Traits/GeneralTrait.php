<?php

namespace App\Traits;

use App\Mail\GeneralEmail;
use App\Mail\VerifyEmail;
use App\Models\PersonalToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

trait GeneralTrait
{
    public function creatCode()
    {
        $code = '';
        for($i = 0; $i < 6; $i++){
            $code .= rand(0, 9);
        }
        return $code;
    }

    // public function sendEmail(Request $request,$data){
    //     Mail::to($request->email)->send(new GeneralEmail($data));
    //     return $this->returnSuccessMessage('send email success');
    // }


    public function mainResponse($status, $message, $data, $error = [], $code = 200)
    {
        $errors = [];
        foreach($error as $key => $value)
        {
            $errors[] = ['filed_name' => $key, 'message' => $value];
        }
        return response()->json(compact('code', 'status', 'message', 'data', 'errors'), 200);
    }

    public function arabicSlug($string, $separator = '-')
    {
        $string = preg_replace('/[\x{200C}-\x{200D}]/u', '', $string); // Remove zero-width non-joiner and non-joiner characters
        $string = preg_replace('/\s/u', $separator, $string); // Replace spaces with the separator
        $string = preg_replace('/[^؀-ۿA-Za-z0-9\-_]/u', '', $string); // Remove any other non-Arabic, non-alphanumeric characters
        $string = preg_replace('/'.$separator.'{2,}/', $separator, $string); // Replace consecutive separators with a single one
        $string = trim($string, $separator); // Trim separators from the beginning and end

        return $string;
    }
    public function checkTokenInfo(Request $request){
        $token = PersonalToken::findOrFail(is_array($request->token) ? $request->token['user_id'] : json_decode($request->token)->id);
        if(Hash::check(is_array($request->token) ? $request->token['returnedToken'] : json_decode( $request->token)->returnedToken, $token->token)/*check tokenable id tokenable type*/){
            return $this->mainResponse(true, 'token checked successfully', $token);
        }
        return $this->mainResponse(false, 'token checked fail', [], ['token' => 'token checked fail'], 422);
    }

    // public function returnSuccessMessage($msg = "")
    // {
        // return response()->json([
        //     'status' => true,
        //     'msg' => $msg
        // ]);
    // }

    // public function returnData($key, $value, $msg = "")
    // {
    //     return response()->json([
    //         'status' => true,
    //         'msg' => $msg,
    //         $key => $value
    //     ]);
    // }

    // public function returnError($errNum, $msg)
    // {
    //     return response()->json([
    //         'status' => false,
    //         'errNum' => $errNum,
    //         'msg' => $msg
    //     ]);
    // }

    // public function returnValidationError($msg, $errors, $code = "E001")
    // {
    //     return response()->json([
    //         'status' => false,
    //         'errNum' => $code,
    //         'msg' => $msg,
    //         'errors' => $errors
    //     ]);
    // }


    // public function returnCodeAccordingToInput($validator)
    // {
    //     $inputs = array_keys($validator->errors()->toArray());
    //     $code = $this->getErrorCode($inputs[0]);
    //     return $code;
    // }

    // public function getErrorCode($input)
    // {
    //     if ($input == "name")
    //         return 'E001';

    //     else if ($input == "password")
    //         return 'E002';

    //     else if ($input == "mobile")
    //         return 'E003';

    //     else if ($input == "id_number")
    //         return 'E004';

    //     else if ($input == "birth_date")
    //         return 'E005';

    //     else if ($input == "agreement")
    //         return 'E006';

    //     else if ($input == "email")
    //         return 'E007';

    //     else if ($input == "city_id")
    //         return 'E008';

    //     else if ($input == "insurance_company_id")
    //         return 'E009';

    //     else if ($input == "activation_code")
    //         return 'E010';

    //     else if ($input == "longitude")
    //         return 'E011';

    //     else if ($input == "latitude")
    //         return 'E012';

    //     else if ($input == "id")
    //         return 'E013';

    //     else if ($input == "promocode")
    //         return 'E014';

    //     else if ($input == "doctor_id")
    //         return 'E015';

    //     else if ($input == "payment_method" || $input == "payment_method_id")
    //         return 'E016';

    //     else if ($input == "day_date")
    //         return 'E017';

    //     else if ($input == "specification_id")
    //         return 'E018';

    //     else if ($input == "importance")
    //         return 'E019';

    //     else if ($input == "type")
    //         return 'E020';

    //     else if ($input == "message")
    //         return 'E021';

    //     else if ($input == "reservation_no")
    //         return 'E022';

    //     else if ($input == "reason")
    //         return 'E023';

    //     else if ($input == "branch_no")
    //         return 'E024';

    //     else if ($input == "name_en")
    //         return 'E025';

    //     else if ($input == "name_ar")
    //         return 'E026';

    //     else if ($input == "gender")
    //         return 'E027';

    //     else if ($input == "nickname_en")
    //         return 'E028';

    //     else if ($input == "nickname_ar")
    //         return 'E029';

    //     else if ($input == "rate")
    //         return 'E030';

    //     else if ($input == "price")
    //         return 'E031';

    //     else if ($input == "information_en")
    //         return 'E032';

    //     else if ($input == "information_ar")
    //         return 'E033';

    //     else if ($input == "street")
    //         return 'E034';

    //     else if ($input == "branch_id")
    //         return 'E035';

    //     else if ($input == "insurance_companies")
    //         return 'E036';

    //     else if ($input == "photo")
    //         return 'E037';

    //     else if ($input == "logo")
    //         return 'E038';

    //     else if ($input == "working_days")
    //         return 'E039';

    //     else if ($input == "insurance_companies")
    //         return 'E040';

    //     else if ($input == "reservation_period")
    //         return 'E041';

    //     else if ($input == "nationality_id")
    //         return 'E042';

    //     else if ($input == "commercial_no")
    //         return 'E043';

    //     else if ($input == "nickname_id")
    //         return 'E044';

    //     else if ($input == "reservation_id")
    //         return 'E045';

    //     else if ($input == "attachments")
    //         return 'E046';

    //     else if ($input == "summary")
    //         return 'E047';

    //     else if ($input == "user_id")
    //         return 'E048';

    //     else if ($input == "mobile_id")
    //         return 'E049';

    //     else if ($input == "paid")
    //         return 'E050';

    //     else if ($input == "use_insurance")
    //         return 'E051';

    //     else if ($input == "doctor_rate")
    //         return 'E052';

    //     else if ($input == "provider_rate")
    //         return 'E053';

    //     else if ($input == "message_id")
    //         return 'E054';

    //     else if ($input == "hide")
    //         return 'E055';

    //     else if ($input == "checkoutId")
    //         return 'E056';

    //     else
    //         return "";
    // }


}
