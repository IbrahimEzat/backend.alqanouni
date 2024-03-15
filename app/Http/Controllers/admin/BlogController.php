<?php

namespace App\Http\Controllers\admin;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\BlogCategory;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BlogController extends Controller
{
    use GeneralTrait;
    public function index()
    {
        $blogs = Blog::with('user:id,name')->orderBy('id', 'desc')->get();
        return $this->mainResponse(true, 'blogs', $blogs, []);
    }

    public function blogCategories(Request $request)
    {
        $categories_id = BlogCategory::where('blog_id', $request->blog_id)->get(['category_id']);
        $ids = [];
        foreach ($categories_id as $cat) {
            array_push($ids, $cat->category_id);
        }
        if ($categories_id)
            return $this->mainResponse(true, '', $ids);
        return $this->mainResponse(false, '', []);
    }

    public function getInfo(Request $request)
    {
        $blog = Blog::where('slug', $request->blog_slug)->first();
        if ($blog)
            return $this->mainResponse(true, '', $blog);
        return $this->mainResponse(false, 'حدث خطا ما', [], [], 422);
    }

    public function updateBlog(Request $request)
    {
        $blogValidate = [
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            'subtitle' => ['required'],
            'image' => ['nullable', 'image']
        ];
        $validator = Validator::make($request->all(), $blogValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'max' => '20 يجب أن لا يزيد عدد الحروف عن ',
            'image' => 'الرجاء إرفاق صورة'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $update_blog = Blog::where('id', $request->blog_id)->first();
        if ($update_blog) {
            DB::beginTransaction();
            try {
                $data['title'] = $request->title;
                $data['subtitle'] = $request->subtitle;
                $data['content'] = $request->content;
                $data['slug'] = $this->arabicSlug($update_blog->slug);
                $cover_name = '';
                if ($cover = $request->file('image')) {
                    $cover_name = time() . '.' . $cover->getClientOriginalExtension();
                    $cover->move(public_path('uploads/blogs/'), $cover_name);
                    $data['image'] = $cover_name;
                }
                $update_blog->update($data);
                BlogCategory::where('blog_id', $request->blog_id)->delete();
                for ($i = 0; $i < count(json_decode($request->category_ids)); $i++) {
                    BlogCategory::create(['blog_id' => $request->blog_id, 'category_id' => json_decode($request->category_ids)[$i]]);
                }
                // topics
                $update_blog->topics()->delete();
                for ($i = 0; $i < count(json_decode($request->topic_ids)); $i++) {
                    $update_blog->topics()->create([
                        'topic_id' => json_decode($request->topic_ids)[$i],
                        'section_type' => 'blog',
                        'section_id' => $update_blog->id,
                    ]);
                }
                DB::commit();
                return $this->mainResponse(true, 'تم تعديل المقالة بنجاح', $update_blog, []);
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
                // return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => 'حدث خطأ ما'], 422);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
    }

    public function acceptBlog(Request $request)
    {
        $accept_blog = Blog::where('id', $request->blog_id)->first();
        if ($accept_blog) {
            DB::beginTransaction();
            try {
                $accept_blog->update([
                    'status' => 'active'
                ]);
                $count = Blog::where('status', 'active')->get()->count();
                $user = User::where('id', $accept_blog->user_id)->first();
                if ($count == 1) {
                    if ($user->type != 'admin') {
                        $user->update(['points' => 6000 + $user->points]);
                    }
                } else {
                    if ($user->type != 'admin') {
                        $user->update(['points' => 3000 + $user->points]);
                    }
                }
                $dataNotify = [
                    'userNotifyId' => $accept_blog->user_id,
                    'avatar' => '',
                    'message' => ' قام الأدمن باعتماد المقالة الخاصة بك والتي بعنوان' . ' ' . $accept_blog->title,
                    'url' => '/blog/' . $accept_blog->slug,
                ];
                DB::commit();
                return $this->mainResponse(true, 'تم تعديل الحالة بنجاح', $dataNotify, []);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => 'حدث خطأ ما'], 422);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
    }

    public function deleteBlog(Request $request)
    {
        $delete_blog = Blog::where('id', $request->blog_id)->first();
        if ($delete_blog) {
            $dataNotify = [
                'userNotifyId' => $delete_blog->user_id,
                'avatar' => '',
                'message' => ' قام الأدمن بحذف المقالة الخاصة بك والتي بعنوان' . ' ' . $delete_blog->title .
                    ' بسبب ' . $request->reason_delete,
                'url' => '',
            ];
            $delete_blog->delete();
            return $this->mainResponse(true, 'تم حذف المقالة بنجاح', $dataNotify, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }
    
    public function topicBlogs(Request $request)
    {
        $blogs = Blog::find($request->blog_id);
        $ids = [];
        foreach ($blogs->topics as $top) {
            array_push($ids, $top->topic_id);
        }
        if ($blogs)
            return $this->mainResponse(true, '', $ids);
        return $this->mainResponse(false, '', []);
    }
}
