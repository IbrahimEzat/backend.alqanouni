<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\staff;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    use GeneralTrait;

    public function addMember(Request $request)
    {
        $Validate = [
            'name' => ['required'],
            'job' => ['required'],
            'image' => ['required', 'image'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
            'image' => 'يجب ارفاق صورة',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $createData = [
            'name' => $request->name,
            'job' => $request->job,
        ];
        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/user-image'), $image_name);
            $createData['image'] = $image_name;
        }

        $member = staff::create($createData);
        if ($member) {
            return $this->mainResponse(true, 'تم اضافة العضو بنجاح ', $member, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }

    public function getAllMember(Request $request)
    {
        $staff = staff::all();
        if ($staff) {
            return $this->mainResponse(true, '', $staff, []);
        }
        return $this->mainResponse(false, 'حدث خطأ  ما ', [], [], 422);
    }

    public function deleteMember(Request $request)
    {
        $Validate = [
            'member_id' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $member = staff::where('id', $request->member_id)->first();
        if ($member) {
            $member->delete();
            return $this->mainResponse(true, 'تم حدف العضو بنجاح', $member, []);
        }
        return $this->mainResponse(false, 'حدث خطأ  ما ', [], [], 422);
    }
    public function updateMember(Request $request)
    {
        $Validate = [
            'member_id' => ['required'],
            'name' => ['required'],
            'job' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $data['name'] = $request->name;
        $data['job'] = $request->job;

        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/user-image'), $image_name);
            $data['image'] = $image_name;
        }


        $member = staff::where('id', $request->member_id)->first();
        if ($member) {
            $member->update($data);
            return $this->mainResponse(true, 'تم تحديث بيانات العضو بنجاح', $member, []);
        }
        return $this->mainResponse(false, 'حدث خطأ  ما ', [], [], 422);
    }
}
