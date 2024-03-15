<?php

namespace App\Http\Controllers\Followings;

use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Followings\Following;
use App\Models\Followings\FollowingCount;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class FollowingController extends Controller
{
    use GeneralTrait;
    public function index()
    {
        return '';
    }
    public function addFollow(Request $request)
    {
        $follow_validate = [
            'following' => ['required']
        ];
        $validator = Validator::make($request->all(), $follow_validate, [
            'required' => 'هذا الحقل مطلوب'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $disable = Following::where(['user_id' => $request->token['user_id'],  'following' => $request->following])->first();
        if ($disable) {
            return $this->mainResponse(false, 'لقد قمت بإضافته من قبل', [], [], 422);
        }
        $follow = Following::create([
            'user_id' => $request->token['user_id'],
            'following' => $request->following
        ]);
        if ($follow) {
            $count =  FollowingCount::where('user_id', $request->following)->first();
            $user = User::where('id', $request->following)->first();
            $count->update([
                'following_count' => ++$count->following_count
            ]);
            $user->update([
                'points' => $user->points + 5
            ]);
            return $this->mainResponse(true, 'تم إضافة متابع جديد', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }
    public function deleteFollow(Request $request)
    {
        $delete_follow = Following::where(['user_id' => $request->token['user_id'], 'following' => $request->following])->first();
        if (!$delete_follow) {
            return $this->mainResponse(false, 'المستخدم غير موجود', [], [], 422);
        }
//        if ($delete_follow->user_id != $delete_follow->following) {
//            return $this->mainResponse(false, 'لا يمكنك إلغاء المتابعة', [], [], 422);
//        }
        $delete = $delete_follow->delete();
        if ($delete) {
            $user = User::where('id', $delete_follow->following)->first();
            $count =  FollowingCount::where('user_id', $delete_follow->following)->first();
            $count->update([
                'following_count' => --$count->following_count
            ]);
            $user->update([
                'points' => $user->points - 5
            ]);
            return $this->mainResponse(true, 'تم إلغاء المتابعة بنجاح', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }

    public function getUserFollowInfo(Request $request)
    {
        $count = FollowingCount::where('user_id', $request->following)->first('following_count');
        $isFollowing = Following::where(['user_id' => $request->token['user_id'], 'following' => $request->following])->first();

        return $this->mainResponse(true, '', ['count' => $count->following_count, 'isFollowing' => (bool)$isFollowing]);
    }
}
