<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Discussion;
use App\Models\Library\Library;
use App\Models\Service;
use App\Models\Surveys\Question;
use App\Models\User;
use App\Models\Exams\Exam;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Competition;


class BannerController extends Controller
{
    use GeneralTrait;

    public function get(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:blog,discussion,survey,library,consultation,service,test,competition',
        ], [
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $banner = Banner::where('type', $request->type)->inRandomOrder()->get();
        if($banner) {
            return $this->mainResponse(true, 'البانرات', $banner);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
    }

    public function getAllBanners(Request $request)
    {
        $banners = Banner::orderBy('type')->get();
        if($banners)
            return $this->mainResponse(true, '', $banners);
    }

    public function getBannerInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_id' => 'required|exists:banners,id',
        ], [
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $banner = Banner::where('id', $request->banner_id)->first();

        if($banner) {
            return $this->mainResponse(true, '', $banner);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cover' => 'required|image',
            'url' => 'required',
            'type' => 'required|in:blog,discussion,survey,library,consultation,service,test,competition',
//            'order' => 'required|numeric|min:1|max:3',
        ], [
//            'order.max' => 'أقصى عدد ممكن هو 3',
            'required' => 'هذا الحقل مطلوب',
            'type.in' => 'اختر قسم من الاقسام المعروضة',
            'cover.image' => 'الرجاء رفع صورة'
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $banner = Banner::where('type', $request->type)->get();
        $image_name = '';
        if(count($banner)  == 3) {
            $firstBanner = Banner::where('type', $request->type)->first();
            if($image = $request->file('cover')){
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/banners/'), $image_name);
            }

            $firstBanner->update([
                'cover' => $image_name,
                'url' => $request->url,
            ]);

            return $this->mainResponse(true, 'تم إنشاء البانر بنجاح', $firstBanner);

        } else {
            if($image = $request->file('cover')){
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/banners/'), $image_name);
            }
            $newBanner = Banner::create([
                'cover' => $image_name,
                'url' => $request->url,
                'type' => $request->type,
//                'order' => $request->order,
            ]);
            if($newBanner) {
                return $this->mainResponse(true, 'تم إنشاء البانر بنجاح', $newBanner);
            }
            return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_id' => 'required|exists:banners,id',
            'cover' => 'nullable|image',
            'url' => 'required|string',
            'type' => 'required|in:blog,discussion,survey,library,consultation,service,test,competition',
//            'order' => 'required|max:3'
        ], [
//            'order.max' => 'أقصى عدد ممكن هو 3',
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $banner = Banner::where('id', $request->banner_id)->first();
        if($banner) {
            $imag = explode('/', $banner->cover);
            $image_name = $imag[count($imag) - 1];
            if($image = $request->file('cover')) {
                $image_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/banners/'), $image_name);
            }
            $banner->update([
                'cover' => $image_name,
                'url' => $request->url,
                'type' => $request->type,
//                'order' => $request->order,
            ]);

            return $this->mainResponse(true, 'تم تعديل البانر بنجاح', $banner);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_id' => 'required|exists:banners,id',
        ], [
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $banner = Banner::where('id', $request->banner_id)->first();

        if($banner) {
            $banner->delete();
            return $this->mainResponse(true, 'تم حذف البانر بنجاح', []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
    }

    public function getStatistics(Request $request)
    {
        $data['blogs'] = Blog::count();
        $data['discussions'] = Discussion::count();
        $data['libraries'] = Library::count();
        $data['users'] = User::count();
        $data['surveys'] = Question::count();
        $data['services'] = Service::count();
        $data['categories'] = Category::count();
        $data['exams'] = Exam::count();
        $data['comtitions'] = Competition::count();
        return $this->mainResponse(true, '', $data);
    }

}
