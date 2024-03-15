<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerifyMail;
use App\Mail\ForgetPasswordMail;
use App\Models\Followings\FollowingCount;
use App\Models\User;
use App\Traits\AuthTrait;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\PersonalToken;
use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //
    use AuthTrait, GeneralTrait;

    public function register(Request $request)
    {
        $createData = [
            'email' => $request->email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'birth_day' => $request->birth_day,
            'job' => $request->job,
            'address' => $request->address,
            'gender' => $request->gender,
        ];
        $regValidate = [
            'email' => ['email', 'required', 'unique:users,email'],
            'name' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'birth_day' => ['required', 'date'],
            'job' => ['string'],
            'address' => ['required', 'string'],
            // 'gender' => ['required']
        ];

        $validator = Validator::make($request->all(), $regValidate, [
            'required' => 'هذا الحقل مطلوب',
            'unique' => '!هذا البريد موجود مسبقا',
            'min' => '8 يجب أن لا يقل عدد الحروف عن ',
            'max' => '50 يجب أن لا يزيد عدد الحروف عن ',
            'email' => 'أدخل بريد صحيح',
            'date' => '!صيغة التاريخ الذي أدخلته غير صحيحة',
            'image' => ' امتداد الصورةغير صحيح',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/user-image'), $image_name);
            $createData['image'] = $image_name;
        } else {
            if ($request->gender == 'male') {
                $createData['image'] = 'male.png';
            } else {
                $createData['image'] = 'female.png';
            }
        }

        DB::beginTransaction();
        try {
            $user = User::create($createData);
            $count = $user->where('type', 'user')->count();

            if ($count == 1) {
                $user->update(['points' => 10000]);
            } elseif ($count == 2) {
                $user->update(['points' => 9000]);
            } elseif ($count == 3) {
                $user->update(['points' => 8000]);
            } elseif ($count == 4) {
                $user->update(['points' => 7000]);
            } elseif ($count == 5) {
                $user->update(['points' => 6000]);
            } elseif ($count == 6) {
                $user->update(['points' => 5000]);
            } elseif ($count == 7) {
                $user->update(['points' => 4000]);
            } elseif ($count == 8) {
                $user->update(['points' => 3000]);
            } elseif ($count == 9) {
                $user->update(['points' => 2000]);
            } elseif ($count == 10) {
                $user->update(['points' => 1000]);
            } elseif ($count >= 11 || $count <= 100) {
                $user->update(['points' => 300]);
            } elseif ($count >= 101 || $count <= 500) {
                $user->update(['points' => 100]);
            }

            if ($user) {
                $token = $this->generateToken($user->id);
                $data['user_id'] = $user->id;
                $data['user_type'] = $user->type;
                $data['user_image'] = $user->image;
                $data['user_gender'] = $user->gender;
                $data['user_name'] = $user->name;
                $data['token'] = $token;
                FollowingCount::create(['user_id'=>$user->id]);
                DB::commit();
                return $this->mainResponse(true, 'تم التسجيل بنجاح', $data);
            }
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ في عملية التسجيل', [], [], 422);
        }
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ], [
            'required' => 'هذا الحقل مطلوب',
            'exists' => '!البريد الذي أدخلته غير موجود',
            'string' => 'الرجاء إدخال نص'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $this->generateToken($user->id);
                $data['user_image'] = $user->image;
                $data['user_type'] = $user->type;
                $data['user_gender'] = $user->gender;
                $data['user_name'] = $user->name;
                $data['token'] = $token;
                return $this->mainResponse(true, 'تم تسجيل الدخول بنجاح', $data);
            } else {
                return $this->mainResponse(false, 'كلمة المرور خطأ', [], ['password' => ['كلمة المرور خطا']], 422);
            }
        } else {
            return $this->mainResponse(false, 'لا يمكن إيجاد هذا المستخدم', [], [], 422);
        }
    }
    public function logout(Request $request)
    {
        $token = PersonalToken::find($request->token['id'])->delete();
        return $this->mainResponse(true, 'logout successfully', []);
    }
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'exists' => '!البريد الذي أدخلته غير موجود',
            'email' => 'الرجاء إدخال بريد صحيح'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        $query = VerificationCode::where('email', $request->email)->first();
        $data = [];
        if ($query) {
            $code = $this->sendMail($request->email);
            $query->update([
                'code' => Hash::make($code),
            ]);
            $data = $query;
        } else {
            $code = $this->sendMail($request->email);
            $newVerify = VerificationCode::create([
                'email' => $request->email,
                'code' => Hash::make($code)
            ]);
            $data = $newVerify;
        }

        return $this->mainResponse(true, 'we send a verify code to your email', $data);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:verification_codes,email',
            'password' => 'required|min:8|confirmed'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'exists' => '!البريد الذي أدخلته غير موجود',
            'min' => '8 يجب أن لا يقل عدد الحروف عن',
            'confirmed' => 'كلمة المرور غير متطابقة',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = VerificationCode::where('email', $request->email)->first();

        if ($query) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
                $query->delete();
                return $this->mainResponse(true, 'تم تحديث كلمة السر بنجاح', []);
            } else {
                return $this->mainResponse(false, '!حدث خطأ ما', [], [], 422);
            }
        }
    }

    public function checkVerifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:verification_codes,email',
            'code' => 'required',
        ], [
            'required' => 'هذا الحقل مطلوب',
            'exists' => '!البريد الذي أدخلته غير موجود',
            'email' => 'الرجاء إدخال بريد صحيح'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = VerificationCode::where('email', $request->email)->first();
        if ($query) {
            if (Hash::check($request->code, $query->code)) {
                return $this->mainResponse(true, '✅تم تأكيد الرقم بنجاح', [], ['code' => ['تم تأكيد الرقم بنجاح']]);
            } else {
                return $this->mainResponse(false, 'الرقم الذي أدخلته غير صحيح، تأكد من الرسالة في بريدك', [], ['code' => ['الرقم الذي أدخلته غير صحيح، تأكد من الرسالة في بريدك']]);
            }
        } else {
            return $this->mainResponse(false, 'حدث خطأ ما', []);
        }
    }

    private function sendMail($email)
    {
        $code = $this->creatCode();
        Mail::to($email)->send(new ForgetPasswordMail($code));
        return $code;
    }
    public function emailVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني للتحقق منه'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = VerificationCode::where('email', $request->email)->first();
        $data = [];
        if ($query) {
            // $code = $this->sendMail($request->email);
            $code = $this->creatCode();
            Mail::to($request->email)->send(new EmailVerifyMail($code));
            $query->update([
                'code' => Hash::make($code),
            ]);
        } else {
            // $code = $this->sendMail($request->email);
            $code = $this->creatCode();
            Mail::to($request->email)->send(new EmailVerifyMail($code));
            $newVerify = VerificationCode::create([
                'email' => $request->email,
                'code' => Hash::make($code)
            ]);
            $data = $newVerify;
        }
        return $this->mainResponse(true, 'تم إرسال رمز التحقق بنجاح', $data);
    }

    public function checkEmailVerifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:verification_codes,email',
            'code' => 'required',
        ], [
            'required' => 'هذا الحقل مطلوب',
            'exists' => '!البريد الذي أدخلته غير موجود',
            'numeric' => 'الرجاء إدخال رقم',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = VerificationCode::where('email', $request->email)->first();
        if ($query) {
            if (Hash::check($request->code, $query->code)) {
                $query->delete();
                return $this->mainResponse(true, '✅تم تأكيد الرقم بنجاح', []);
            } else {
                return $this->mainResponse(false, 'الرقم الذي أدخلته غير صحيح، تأكد من الرسالة في بريدك', []);
            }
        } else {
            return $this->mainResponse(false, 'حدث خطأ ما', []);
        }
    }

    // private function sendMail($email)
    // {
    // $code = $this->creatCode();
    // Mail::to($request->email)->send(new EmailVerifyMail($code));
    //     return $code;
    // }


    public function updateRegisterinfo(Request $request)
    {
        $createData = [
            'name' => $request->name,
            'birth_day' => $request->birth_day,
            'job' => $request->job,
            'address' => $request->address,
            'gender' => $request->gender,
        ];
        $regValidate = [

            'name' => ['required', 'string', 'max:50'],
            // 'password' => ['required', 'string', 'min:8'],
            'birth_day' => ['required', 'date'],
            'job' => ['string'],
            'address' => ['required', 'string'],
            'image' => ['nullable'],
            'gender' => ['required']
        ];

        $validator = Validator::make($request->all(), $regValidate, [
            'required' => 'هذا الحقل مطلوب',
            'unique' => '!هذا البريد موجود مسبقا',
            'min' => '8 يجب أن لا يقل عدد الحروف عن ',
            'max' => '50 يجب أن لا يزيد عدد الحروف عن ',
            'email' => 'أدخل بريد صحيح',
            'date' => '!صيغة التاريخ الذي أدخلته غير صحيحة',
            'image' => ' امتداد الصورةغير صحيح',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $user_update = User::where('id', json_decode($request->token)->user_id)->first();

        $image_name = $user_update->image;

        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/user-image'), $image_name);
            $createData['image'] = $image_name;
        }


        $user_update->update($createData);
        if ($user_update) {
            return $this->mainResponse(true, 'تم التعديل بنجاح', $user_update->image, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء التعديل', [], [], 422);
    }

    public function updatePassword(Request $request)
    {
        $password_validate = [
            'oldPassword' => ['required', 'string', 'min:8'],
            'newPassword' => ['required', 'string', 'min:8'],
            'confirmNewPassword' => ['required', 'string']
        ];
        $validator = Validator::make($request->all(), $password_validate, [
            'required' => 'هذا الحقل مطلوب',
            'min' => '8 يجب أن لا يقل عدد الحروف عن ',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $user = User::where('id', $request->token['user_id'])->first();

        if ($user) {
            if (Hash::check($request->oldPassword, $user->password)) {
                if ($request->newPassword == $request->confirmNewPassword) {
                    $user->update([
                        'password' => Hash::make($request->newPassword)
                    ]);
                    if ($user) {
                        return $this->mainResponse(true, 'تم التعديل بنجاح', [], []);
                    }
                }
                return $this->mainResponse(false, 'يرجى التحقق من كلمة السر المدخلة', [], [], 422);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء التعديل', [], [], 422);
    }
    public function getInfo(Request $request)
    {
        $user = User::where('id', $request->token['user_id'])->first(['name', 'birth_day', 'job', 'address', 'gender', 'image', 'about_me']);
        return $this->mainResponse(true, 'user', $user, []);
    }

    public function updateAboutMe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'about_me' => ['required', 'string', 'nullable'],
        ], [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $about_user = User::where('id', $request->token['user_id'])->first();
        if ($about_user) {
            if ($about_user->points >= 500) {
                $about_user->update([
                    'about_me' => $request->about_me,
                    'points' => $about_user->points - 500
                ]);
                if ($about_user) {
                    return $this->mainResponse(true, 'تم إضافة نبذة عنك بنجاح', [], []);
                }
            }
            return $this->mainResponse(false, 'لا يوجد في رصيدك عدد كافي من النقاط', [], [], 422);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء الإضافة', [], [], 422);
    }
    
        public function checkEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if($user) {
            return $this->mainResponse(false, 'الايميل موجود مسبقا', []);
        }
        return $this->mainResponse(true, '', []);
    }
}
