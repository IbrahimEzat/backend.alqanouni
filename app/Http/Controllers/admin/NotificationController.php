<?php

namespace App\Http\Controllers\admin;

use App\Events\admin\AddFile;
use App\Events\admin\AddSurvey;
use App\Events\admin\BlogCreatedEvent;
use App\Events\admin\CreateDiscussion;
use App\Events\admin\GiveUserBadge;
use App\Events\admin\GiveUserPoints;
use App\Events\admin\NewServiceSubscribe;
use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\CustomNotification;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function storeNotificationDB(Request $request)
    {
        $dataReturned = CustomNotification::create([
            'id'=>fake()->uuid(),
            'notifyId' => User::where('type','admin')->first(['id'])->id,
            'data' => json_encode($request->dataNotify),
        ]);
        $dataReturned->refresh();
        return $dataReturned;
    }
    public function sendNewSubscribeNotify(Request $request){
        $data = $this->storeNotificationDB($request);
        broadcast(new NewServiceSubscribe($data));
    }
    public function newBlog(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        // $data = User::where('type', 'admin')->first()->notify(new StoreNotification($request->dataNotify));
        broadcast(new BlogCreatedEvent($data));
    }
    public function addDiscussion(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new CreateDiscussion($data));
    }
    public function addFile(Request $request){
        $data = $this->storeNotificationDB($request);
        broadcast(new AddFile($data));
    }

    public function addSurvey(Request $request){
        $data = $this->storeNotificationDB($request);
        broadcast(new AddSurvey($data));
    }
    public function givePoints(Request $request)
    {

        $message =  'تم' . ' ' . ($request->points > 0 ? 'منحك' : 'خصم') . ' ' . abs($request->points) . ' نقطة بسبب ' . $request->message;
        if ($request->points == 0)
            $message = $request->message;
        foreach ($request->users as $key => $userId) {

            $dataNotify = [
                'userNotifyId' => $userId,
                'avatar' => null,
                'message' => $message,
                'url' => null,
            ];
            $dataReturned = CustomNotification::create([
                'id' => fake()->uuid(),
                'notifyId' => $userId,
                'data' => json_encode($dataNotify),
            ]);
            $dataReturned->refresh();
            $user = User::where('id', $userId)->first();
            $user->update(['points' => $user->points + $request->points]);
            broadcast(new GiveUserPoints($dataReturned));
        }
    }
    
    public function giveBadges(Request $request)
    {
        $badge = Badge::where('id', $request->badge_id)->first(['name']);
        foreach ($request->users as $key => $userId) {
            $dataNotify = [
                'userNotifyId' => $userId,
                'avatar' => null,
                'message' => 'تم منحك' . ' ' . $badge->name . ' ' . ' من قبل الموقع شكرا لك',
                'url' => null,
            ];
            $dataReturned = CustomNotification::create([
                'id' => fake()->uuid(),
                'notifyId' => $userId,
                'data' => json_encode($dataNotify),
            ]);
            $dataReturned->refresh();
            $query = UserBadge::where(['user_id' => $userId, 'badge_id' => $request->badge_id])->first();
            if ($query) {
                $query->update([
                    'count' => $query->count + 1
                ]);
            } else {
                UserBadge::create([
                    'user_id' => $userId,
                    'badge_id' => $request->badge_id,
                ]);
            }
            broadcast(new GiveUserBadge($dataReturned));
        }
    }
}
