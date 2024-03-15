<?php

namespace App\Http\Controllers\admin;

use App\Models\Topic;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    //
    use GeneralTrait;
    public function index()
    {
        $categories = Category::all();
        return $this->mainResponse(true, 'get all categories', $categories, []);
    }

    public function storeCategory(Request $request)
    {
        $categoryValidate = [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required'],
            'background' => ['required'],

        ];
        $validator = Validator::make($request->all(), $categoryValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'max' => '50 يجب أن لا يزيد عدد الحروف عن ',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $add_category = Category::create([
            'name' => $request->name,
            'slug' => $this->arabicSlug($request->name),
            'color' => $request->color,
            'background' => $request->background,
        ]);
        if ($add_category) {
            return $this->mainResponse(true, 'تم اضافة تصنيف بنجاح', $add_category, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }

    public function updateCategory(Request $request)
    {
        $categoryValidate = [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required'],
            'background' => ['required'],

        ];
        $validator = Validator::make($request->all(), $categoryValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'max' => '50 يجب أن لا يزيد عدد الحروف عن ',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $update_categoy = Category::where('slug', $request->slug)->first();
        if ($update_categoy) {
            $update_categoy->update([
                'name' => $request->name,
                'slug' => $this->arabicSlug($request->name),
                'color' => $request->color,
                'background' => $request->background,

            ]);
            return $this->mainResponse(true, 'تم تعديل تصنيف بنجاح', $update_categoy, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
    }

    public function deleteCategory(Request $request)
    {
        $delete_categoy = Category::where('slug', $request->slug)->first();
        if ($delete_categoy) {
            $delete_categoy->delete();
            return $this->mainResponse(true, 'تم حذف التصنيف بنجاح', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }

    public function addTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:topics,name',
            'image' => 'required|image',
            'image.*' => 'mimes:jpg,jpeg,png,gif'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'image.image' => 'الرجاء إرفاق صورة',
            'image.mimes' => 'فقط هذه الاختصارات هي المتاحة: jpg,jpeg,png,gif',
            'unique' => 'هذا الاسم موجود مسبقا',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        DB::beginTransaction();
        try {
            $cover_name = '';
            if ($cover = $request->file('image')) {
                $cover_name = time() . '.' . $cover->getClientOriginalExtension();
                $cover->move(public_path('uploads/topics/images'), $cover_name);
            }

            $topic = Topic::create([
                'name' => $request->name,
                'image' => $cover_name,
                'slug' => $this->arabicSlug($request->name),
                'type' => $request->type
            ]);

            if ($topic) {
                DB::commit();
                return $this->mainResponse(true, 'تم إنشاء التصنيف بنجاح', $topic);
            }
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
            return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
        }
    }

    public function allTopics()
    {
        $topics = Topic::orderBy('id', 'desc')->get();
        return $this->mainResponse(true, 'كل التصنيفات في الموقع', $topics);
    }

    public function getTopicDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|exists:topics,slug'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $topic = Topic::where('slug', $request->slug)->first();
        return $this->mainResponse(true, 'هذه بيانات التصنيف الذي طلبته', $topic);
    }

    public function updateTopic(Request $request)
    {
        $topic = Topic::where('slug', $request->slug)->first();
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:topics,name,' .$topic->id,
            'image' => 'nullable',
            'image.*' => 'mimes:jpg,jpeg,png,gif'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'image.image' => 'الرجاء إرفاق صورة',
            'image.mimes' => 'فقط هذه الاختصارات هي المتاحة: jpg,jpeg,png,gif',
            'unique' => 'هذا الاسم موجود مسبقا',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        DB::beginTransaction();
        try {
            $data['name'] = $request->name;
            $data['slug'] = $this->arabicSlug($request->name);
            $data['type'] = $request->type;
            $cover_name = '';
            if ($cover = $request->file('image')) {
                $cover_name = time() . '.' . $cover->getClientOriginalExtension();
                $cover->move(public_path('uploads/topics/images'), $cover_name);
                $data['image'] = $cover_name;
            }

            $topic->update($data);
            DB::commit();
            return $this->mainResponse(true, 'تم تعديل التصنيف بنجاح', []);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
            return $this->mainResponse(false, 'حدث خطأ ما، يرجى المحاولة لاحقا', [], ['error' => ['حدث خطأ ما، يرجى المحاولة لاحقا']], 422);
        }
    }

    public function deleteTopic(Request $request)
    {
        $delete_topic = Topic::where('slug', $request->slug)->first();
        if ($delete_topic) {
            $delete_topic->delete();
            return $this->mainResponse(true, 'تم حذف التصنيف بنجاح', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);

    }
}
