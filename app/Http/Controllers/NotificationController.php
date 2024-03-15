<?php

namespace App\Http\Controllers;

use App\Events\NewServiceSubscribeMessage;
use App\Events\NewSubscribeMessageNotify;
use App\Events\user\AcceptDiscussion;
use App\Events\user\AcceptFile;
use App\Events\user\AcceptSurvey;
use App\Events\user\AddCommenteFile;
use App\Events\user\AddOpinionDiscussion;
use App\Events\user\BlogAcceptEvent;
use App\Events\user\BlogDeleteEvent;
use App\Events\user\CommentEvent;
use App\Events\user\DeleteDiscussion;
use App\Events\user\DeleteFile;
use App\Events\user\DeleteSurvey;
use App\Events\user\NewMessageChatEvent;
use App\Events\user\ServiceSubscriptionSubmit;
use App\Models\Blog;
use App\Models\Chat;
use App\Models\CustomNotification;
use App\Models\Service;
use App\Models\ServiceSubscription;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Events\user\CompetitionCorrectEvent;
use App\Events\user\CompetitionPrizesEvent;


class NotificationController extends Controller
{

    use GeneralTrait;
    
    public function getUnread(Request $request)
    {
        $unReadNotification = CustomNotification::where(['notifyId' => $request->user_id, 'read_at' => false])->get();
        return $this->mainResponse(true, '', $unReadNotification);
    }

    public function newServiceSubscribeMessage(Request $request){
        broadcast(new NewServiceSubscribeMessage($request->dataNotify));
        if(!$request->withNotify)return;
        $sender = User::where('id',$request->dataNotify['sender_id'])->first();
        $subscribe = ServiceSubscription::where('id',$request->dataNotify['subscribe_id'])->first(['service_id','user_id']);
        $service = Service::where('id',$subscribe->service_id)->first('title');
        $notifyId = $sender->id == $subscribe->user_id ? User::where('type','admin')->first('id')->id : $subscribe->user_id;
        $dataNotify = [
            'userNotifyId' => $notifyId,
            'avatar' => $sender->image,
            'message' => 'أضاف'. ' ' . $sender->name . ' ردا على الخدمة: '.$service->title,
            'url' => "/services/holdings/".$request->dataNotify['subscribe_id']."/details",
        ];
        $dataReturned = CustomNotification::create([
            'id' => fake()->uuid(),
            'notifyId' => $notifyId,
            'data' => json_encode($dataNotify),
        ]);
        $dataReturned->refresh();
        broadcast(new NewSubscribeMessageNotify($dataReturned));
    }

    public function getUnreadNotificationCount(Request $request)
    {
        $count = CustomNotification::where(['notifyId' => $request->user_id, 'read_at' => false])->count();
        return $this->mainResponse(true, '', $count);
    }

    public function markAsRead($id)
    {
        $notification = CustomNotification::where('id', $id)->first();
        $notification->update([
            'read_at' => true
        ]);
    }
    
    public function loadMore(Request $request, $offset, $count)
    {
        $notifications = CustomNotification::where(['notifyId' => $request->user_id])->orderBy('created_at', 'desc')->skip($offset)->take($count)->get();
        return $this->mainResponse(true, '', $notifications);
    }

    private function storeNotificationDB(Request $request)
    {
        $dataReturned = CustomNotification::create([
            'id' => fake()->uuid(),
            'notifyId' => $request->dataNotify['userNotifyId'],
            'data' => json_encode($request->dataNotify),
        ]);
        $dataReturned->refresh();
        return $dataReturned;
    }
    
    public function acceptBlog(Request $request)
    {
        // $data = Notification::send($user,new StoreNotification($request->dataNotify));
        // $data = ->notify(new StoreNotification($request->dataNotify));
        $data = $this->storeNotificationDB($request);
        broadcast(new BlogAcceptEvent($data));
    }


    public function newChatMessage(Request $request)
    {

        $chat = Chat::where('id', $request->chat_id)
            ->with(['user1:id,name,image', 'user2:id,name,image', 'lastChatMessage:chat_id,last_message_id,is_read', 'lastChatMessage.message:id,message,user_send_id,created_at'])->first();
            broadcast(new NewMessageChatEvent($chat));
    }

    public function deleteBlog(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new BlogDeleteEvent($data));
    }
    
    public function commentBlog(Request $request)
    {
        $blog = Blog::where('id', $request->blog_id)->first();
        $dataNotify = [
            'userNotifyId' => $blog->user_id,
            'avatar' => $request->user_image,
            'message' => 'تم اضافة تعليق جديد على مقالتك ' . $blog->title,
            'url' => '/blog/' . $blog->slug,
        ];
        $dataReturned = CustomNotification::create([
            'id' => fake()->uuid(),
            'notifyId' => $blog->user_id,
            'data' => json_encode($dataNotify),
        ]);
        $dataReturned->refresh();
        broadcast(new CommentEvent($dataReturned));
    }


    public function deleteDiscussion(Request $request)
    {
        $data = $this->storeNotificationDB($request);

        broadcast(new DeleteDiscussion($data));
        // User::where('id',$request->dataNotify['user']['id'])->first()->notify(new StoreNotification($request->dataNotify));
    }

    public function acceptDiscussion(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new AcceptDiscussion($data));
        // User::where('id',$request->dataNotify['user']['id'])->first()->notify(new StoreNotification($request->dataNotify));
    }
    
    public function addOpinDiscussion(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new AddOpinionDiscussion($data));
        // User::where('id',$request->dataNotify['user']['id'])->first()->notify(new StoreNotification($request->dataNotify));
    }

    public function acceptFile(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new AcceptFile($data));
    }

    public function deleteFile(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new DeleteFile($data));
    }
    
    public function addCommentFile(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new AddCommenteFile($data));
    }

    public function acceptSurvey(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new AcceptSurvey($data));
    }

    public function deleteSurvey(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new DeleteSurvey($data));
    }

    public function serviceApproval(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new ServiceSubscriptionSubmit($data));
    }
    
        public function competitionCorrect(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new CompetitionCorrectEvent($data));
    }

    public function competitionPrizes(Request $request)
    {
        $data = $this->storeNotificationDB($request);
        broadcast(new CompetitionPrizesEvent($data));
    }
}
