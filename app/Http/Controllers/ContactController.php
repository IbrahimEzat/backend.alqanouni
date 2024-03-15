<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use GeneralTrait;

    public function store(Request $request)
    {
        $rules = [];
        // $auth = $request->token['user_id'] == -1;
        if ($request->token['user_id'] == -1) {
            $rules = [
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                'message' => 'required',
            ];
        } else {
            $rules = [
                'message' => 'required',
            ];
        }

        $validator = Validator::make($request->all(), $rules, [
            'required' => 'هذا الحقل مطلوب',
            'email' => 'الرجاء إدخال ايميل',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        if ($request->token['user_id'] == -1) {
            $data = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
            ]);
            return $this->mainResponse(true, 'تم إرسال البيانات بنجاح', $data, []);
        } else {
            $user = User::where('id', $request->token['user_id'])->first(['name', 'email']);
            if ($user) {
                $data = Contact::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'message' => $request->message,
                ]);
            return $this->mainResponse(true, 'تم إرسال البيانات بنجاح', $data, []);

            }
            return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']]);
        }
    }

    public function getAllContacts()
    {
        $contacts = Contact::orderBy('id', 'desc')->get();
        return $this->mainResponse(true, 'هذه كل طلبات التواصل', $contacts, []);
    }
}
