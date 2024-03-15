<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceSubscription;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    use GeneralTrait;

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:services,title',
            'cover' => 'required|image',
            'details' => 'required',
            'points' => 'required|numeric|min:1|max:9999',
            'images' => 'required',
            'images.*' => 'mimes:jpg,jpeg,png,gif'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'title.max' => 'أقصى عدد من الحروف 255',
            'cover.image' => 'الرجاء إرفاق صورة',
            'points.min' => 'أقل عدد من النقاط 1',
            'points.max' => 'أكبر قيمة لنقاط الخدمة هو 9999',
            'images.mimes' => 'فقط هذه الاختصارات هي المتاحة: jpg,jpeg,png,gif '
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        DB::beginTransaction();
        try {
            $cover_name = '';
            if ($cover = $request->file('cover')) {
                $cover_name = time() . '.' . $cover->getClientOriginalExtension();
                $cover->move(public_path('uploads/services/covers'), $cover_name);
            }

            $service = Service::create([
                'title' => $request->title,
                'slug' => $this->arabicSlug($request->title),
                'cover' => $cover_name,
                'details' => $request->details,
                'points' => $request->points
            ]);

            if ($service) {
                if ($request->images && count($request->images) > 0) {
                    $i = 0;
                    foreach ($request->images as $image) {
                        $file_name = $service->slug . '_' . time() . '_' . $i . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('uploads/services/images'), $file_name);
                        $service->images()->create([
                            'file_name' => $file_name,
                        ]);
                        $i++;
                    }
                }
                DB::commit();
                return $this->mainResponse(true, 'تم إنشاء الخدمة بنجاح', $service);
            }
        } catch (\Throwable $throwable) {
            Db::rollBack();
            throw $throwable;
            return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);

        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:services,slug',
            'title' => 'required|string|max:255',
            'cover' => 'nullable|image',
            'details' => 'required',
            'points' => 'required|numeric|min:1',
            'images' => 'nullable',
            'images.*' => 'mimes:jpg,jpeg,png,gif|max:3000'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'title.max' => 'أقصى عدد من الحروف 255',
            'cover.image' => 'الرجاء إرفاق صورة',
            'points.min' => 'أقل عدد من النقاط 1',
            'images.mimes' => 'فقط هذه الاختصارات هي المتاحة: jpg,jpeg,png,gif '
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('slug', $request->slug)->first();
        if ($service) {
            DB::beginTransaction();
            try {
                $imag = explode('/', $service->cover);
                $cover_name = $imag[count($imag) - 1];
                if ($cover = $request->file('cover')) {
                    $cover_name = time() . '.' . $cover->getClientOriginalExtension();
                    $cover->move(public_path('uploads/services/covers'), $cover_name);
                }
                $service->update([
                    'title' => $request->title,
                    'slug' => Str::slug($request->title),
                    'cover' => $cover_name,
                    'details' => $request->details,
                    'points' => $request->points
                ]);


                if ($request->images && count($request->images) > 0) {
                    $i =  $service->images()->count();
                    foreach ($request->images as $image) {
                        $file_name = $service->slug . '_' . time() . '_' . $i . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('uploads/services/images'), $file_name);
                        $service->images()->create([
                            'file_name' => $file_name,
                        ]);
                        $i++;
                    }
                }
                DB::commit();
                return $this->mainResponse(true, 'تم تعديل الخدمة بنجاح', $service);

            } catch (\Throwable $throwable) {
                Db::rollBack();
                return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);

            }
        }
    }

    public function getServices(Request $request)
    {
        $services = Service::orderBy('id', 'desc')->withCount('reviews')->withAvg('reviews', 'rating')->get();

        if($services) {
            return $this->mainResponse(true, '', $services);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function getServiceInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:services,slug'
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('slug', $request->slug)->with('images')->first();
        if($service) {
            return $this->mainResponse(true, '', $service);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function getAllSubscriptions(Request $request)
    {
        $services = ServiceSubscription::with(['service:id,title,slug', 'user:id,name'])->orderBy('id','desc')
            ->get(['id', 'service_id', 'user_id', 'status']);
        if($services)
            return $this->mainResponse(true, 'هذه كل الخدمات في الموقع', $services);

        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }


    public function deleteService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id'
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $service = Service::where('id', $request->service_id)->first();
        if($service) {
            $service->delete();
            return $this->mainResponse(true, '', []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);

    }
}
