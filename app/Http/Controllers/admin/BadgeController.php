<?php

namespace App\Http\Controllers\admin;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class BadgeController extends Controller
{
    use GeneralTrait;

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:badges,id',
            'image' => 'required|image'
        ], [
            'unique' => 'هذا الاسم تم استخدامه من قبل',
            'image' => 'الرجاء إرفاق صورة',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $data['name'] = $request->name;

        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/badges'), $image_name);
            $data['image'] = $image_name;
        }

        $badge = Badge::create($data);
        if ($badge) {
            return $this->mainResponse(true, 'تم إضافة وسام بنجاح', $badge, []);
        }

        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:badges,name,' . $request->badge_id,
            'image' => 'nullable'
        ], [
            'unique' => 'هذا الاسم تم استخدامه من قبل',
            'image' => 'الرجاء إرفاق صورة',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $data['name'] = $request->name;

        if ($image = $request->file('image')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/badges'), $image_name);
            $data['image'] = $image_name;
        }

        $badge = Badge::where('id', $request->badge_id)->first();

        if ($badge) {
            $badge->update($data);
            return $this->mainResponse(true, 'تم تعديل الوسام بنجاح', $badge, []);
        }

        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function grant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'badge_id' => 'required|exists:badges,id',
            'users' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        DB::beginTransaction();
        try {
            foreach ($request->users as $user) {
                $query = UserBadge::where(['user_id' => $user, 'badge_id' => $request->badge_id])->first();
                if ($query) {
                    $query->update([
                        'count' => $query->count + 1
                    ]);
                } else {
                    UserBadge::create([
                        'user_id' => $user,
                        'badge_id' => $request->badge_id,
                    ]);
                }
            }
            DB::commit();
            return $this->mainResponse(true, 'تم منح الوسام بنجاح', []);
        } catch (\Throwable $throwable) {
            throw $throwable;
            DB::rollback();
            // return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
        }
    }

    public function getBadges()
    {
        $badges = Badge::all();
        return $this->mainResponse(true, 'هذه كل الاوسمة في الموقع', $badges);
    }

    public function getUserBadges(Request $request)
    {
        $data = User::where('id', $request->user_id)->with(['badges.badge:id,name,image'])->first(['id']);

        if ($data) {
            return $this->mainResponse(true, 'هذه الاوسمة التي حصلت عليها في الموقع', $data);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function delete(Request $request)
    {
        Badge::where('id', $request->badge_id)->delete();
        return $this->mainResponse(true, 'تم حذف الوسام بنجاح', []);
    }
}
