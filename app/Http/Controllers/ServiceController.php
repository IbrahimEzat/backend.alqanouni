<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceReview;
use App\Models\ServiceSubscription;
use App\Models\SubsribeServiceMessages;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    use GeneralTrait;
    public function getServices(Request $request)
    {
        $services = Service::orderBy('id', 'desc')->withCount('reviews')->withAvg('reviews', 'rating')->get();

        if ($services) {
            return $this->mainResponse(true, '', $services);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function getServiceInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:services,slug'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('slug', $request->slug)->with('images:service_id,file_name')->withCount('reviews')->withAvg('reviews', 'rating')->first();

        if ($service) {
            return $this->mainResponse(true, '', $service);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function getReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:services,slug'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('slug', $request->slug)->first('id');

        if ($service) {
            $reviews = ServiceReview::where('service_id', $service->id)->orderBy('id','desc')->with('user:id,name,image')->get();
            return $this->mainResponse(true, '', $reviews);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function checkPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $user = User::where('id', $request->token['user_id'])->first();
        $service = Service::where('id', $request->service_id)->first();

        if ($user->points < $service->points) {
            return $this->mainResponse(false, 'أنت لا تملك نقاط كافية لشراء هذه الخدمة', [], ['points' => ['أنت لا تملك نقاط كافية لشراء هذه الخدمة']], 422);
        }
        return $this->mainResponse(true, 'يرجى تأكيد عملية الشراء', []);
    }

    public function confirmSubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $user = User::where('id', $request->token['user_id'])->first();
        $service = Service::where('id', $request->service_id)->first();

        if ($user && $service) {
            // check point
            $newPoint = $user->points - $service->points;
            if ($newPoint < 0) {
                return $this->mainResponse(false, 'أنت لا تملك نقاط كافية لشراء هذه الخدمة', [], ['points' => ['أنت لا تملك نقاط كافية لشراء هذه الخدمة']], 422);
            }
            $user->update([
                'points' => $newPoint,
            ]);
            $subscription = ServiceSubscription::create([
                'user_id' => $user->id,
                'service_id' => $service->id
            ]);
            $dataNotify = [
                'avatar' => $user->image,
                'message' => 'قام' . ' ' . $user->name . ' ' . 'بشراء خدمة' . ' ' . $service->title,
                'url' => '/services/holdings/' . $subscription->id . '/details'
            ];
            if ($subscription) {
                return $this->mainResponse(true, 'تم شراء الخدمة بنجاح، سنتواصل معك بأقرب وقت ان شاء الله', $dataNotify);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }




    public function checkSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:service_subscriptions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        // $service = Service::where('slug', $request->slug)->first();
        $subscription = ServiceSubscription::where('id', $request->id)->with('service:id,title,points')->first();

        $user = User::where('id', $request->token['user_id'])->first();
        if ($user->type == 'admin' || $subscription->user_id == $request->token['user_id'])
            if ($subscription) {
                return $this->mainResponse(true, '', $subscription);
            }
        return $this->mainResponse(false, '', [], []);
    }

    public function submitService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:service_subscriptions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $subscription = ServiceSubscription::where('id', $request->id)->first();
        $user = User::where('id', $request->token['user_id'])->first();

        if ($user->type == 'admin' || $subscription->user_id == $request->token['user_id'])
            if ($subscription) {
                $subscription->update([
                    'status' => true
                ]);
                $dataNotify = [];
                if($user->type == 'admin') {
                    $dataNotify = [
                        'userNotifyId' => $subscription->user_id,
                        'avatar' => '',
                        'message' => 'قام الأدمن بإعتماد الخدمة التي قمت بشرائها',
                        'url' => '/services/holdings/' . $subscription->id . '/details',
                    ];
                }
                return $this->mainResponse(true, 'تم إعتماد الخدمة بنجاح', $dataNotify);
            }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:service_subscriptions,id',
            'message' => 'required',
            'rating' => 'required|numeric|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        $subscription = ServiceSubscription::where('id', $request->id)->first();
        if ($subscription->user_id != $request->token['user_id'])
            return $this->mainResponse(false, '', [], []);

        $service = Service::where('id', $subscription->service_id)->first();
        if ($service) {
            if ($subscription->status && !$subscription->is_rate) {
                $review = ServiceReview::create([
                    'service_id' => $service->id,
                    'user_id' => $request->token['user_id'],
                    'message' => $request->message,
                    'rating' => $request->rating
                ]);

                if ($review){
                    $subscription->update([
                        'is_rate' => true
                    ]);
                    return $this->mainResponse(true, 'تم إضافة تقييمك بنجاح', [], []);
                }
            } else
                return $this->mainResponse(false, 'لا يمكنك إضافة تقييم', [], [], 422);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function addMessageToSubscribe(Request $request)
    {
        $serviceSubsribe = ServiceSubscription::where('id', $request->subscribe_id)->first();
        $user = User::where('id', json_decode($request->token)->user_id)->first();
        if ($user->type == 'admin' || $user->id == $serviceSubsribe->user_id) {
            $message = $request->message;
            $attachment = null;
            if ($file = $request->file('file')) {
                // $request->
                $file_name = time() . "_" . $file->getClientOriginalName();

                $file->move(public_path('uploads/services/messagesAttach'), $file_name);
                $message = $file->getClientOriginalName();
                $attachment = asset('public/uploads/services/messagesAttach/' . $file_name);
            }
            $result = SubsribeServiceMessages::create([
                'subscribe_id' => $request->subscribe_id,
                'message' => $message,
                'attachment' => $attachment,
                'sender_id' => json_decode($request->token)->user_id,
            ]);
            if ($result) {
                return $this->mainResponse(true, '', $result);
            }
        }
    }

    public function getMessagesInSubscribe(Request $request)
    {
        $serviceSubsribe = ServiceSubscription::where('id', $request->subscribe_id)->first();
        $user = User::where('id', $request->token['user_id'])->first();
        if ($user->type == 'admin' || $user->id == $serviceSubsribe->user_id) {
            $messages = SubsribeServiceMessages::where('subscribe_id', $request->subscribe_id)->orderBy('created_at', 'desc')->get();
            if ($messages)
                return $this->mainResponse(true, '', $messages);
        }
        return $this->mainResponse(false, 'لا يمكن جلب بيانات المراسلة', []);
    }

    public function checkSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:services,slug'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('slug', $request->slug)->first();
        $subscription = ServiceSubscription::where(['user_id' => $request->token['user_id'], 'service_id' => $service->id])->first();
        if ($subscription) {
            if ($subscription->status)
                return $this->mainResponse(true, '', []);
        }
        return $this->mainResponse(false, '', [], [], 422);
    }



    public function getUserServices(Request $request)
    {
        $validator = Validator::make($request->all(), []);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $user = User::where('id', $request->token['user_id'])->first('id');
        if ($user) {
            $services = ServiceSubscription::where('user_id', $request->token['user_id'])
            ->orderBy('id','desc')
            ->with('service:id,title,slug,points,cover')
            ->get(['id', 'service_id', 'status','created_at']);

            if ($services)
                return $this->mainResponse(true, 'هذه كل الخدمات التي اشتركت بها', $services);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }
}
