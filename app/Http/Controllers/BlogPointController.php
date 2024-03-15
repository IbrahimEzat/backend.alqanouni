<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogPoint;
use App\Models\BlogUserPoint;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogPointController extends Controller
{
    use GeneralTrait;


    public function increase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        $blogUserPoint = BlogUserPoint::where(['blog_id' => $request->blog_id, 'user_id'=> $request->user_id])->first();

        if (!$blogUserPoint) {
            BlogUserPoint::create([
                'blog_id' => $request->blog_id,
                'user_id' => $request->user_id,
                'rateType' => 'add'
            ]);
            $blog = BlogPoint::where('blog_id', $request->blog_id)->first();


                $blog->update(['blog_points' => $blog->blog_points + 1]);
                $userBlog = Blog::where('id', $request->blog_id)->first()->user_id;
                $user = User::find($userBlog);
                $userPoints = $user->points;
                $user->update([
                    'points' => $userPoints++
                ]);
                return $this->mainResponse(true, $blog, 'تم زيادة النقاط بنجاح', []);

        } else if ($blogUserPoint->rateType == 'minus') {
            $blogUserPoint->update([
                'rateType' => 'add'
            ]);
            $blog = BlogPoint::where('blog_id', $request->blog_id)->first();

                $blog->update(['blog_points' => $blog->blog_points + 2]);
                $userBlog = Blog::where('id', $request->blog_id)->first()->user_id;
                $user = User::find($userBlog);
                $user->update([
                    'points' => $user->points +1
                ]);
                return $this->mainResponse(true, $blog, 'تم زيادة النقاط بنجاح', []);

        }else{
        return $this->mainResponse(false, 'لقد قمت بتقييم هذة المقالة سابقا', [], [], 422);

        }



        return $this->mainResponse(false, 'حدث خطأ ما', [], [], 422);
    }


    public function decrease(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }


        $blogUserPoint = BlogUserPoint::where(['blog_id' => $request->blog_id, 'user_id'=> $request->user_id])->first();

        if (!$blogUserPoint) {
            BlogUserPoint::create([
                'blog_id' => $request->blog_id,
                'user_id' => $request->user_id,
                'rateType' => 'minus'
            ]);
            $blog = BlogPoint::where('blog_id', $request->blog_id)->first();


                $blog->update(['blog_points' => $blog->blog_points - 1]);
                $userBlog = Blog::where('id', $request->blog_id)->first()->user_id;
                return $this->mainResponse(true, $blog, 'تم زيادة النقاط بنجاح', []);

        } else if ($blogUserPoint->rateType == 'add') {
            $blogUserPoint->update([
                'rateType' => 'minus'
            ]);
            $blog = BlogPoint::where('blog_id', $request->blog_id)->first();

                $blog->update(['blog_points' => $blog->blog_points - 2]);
                return $this->mainResponse(true, $blog, 'تم زيادة النقاط بنجاح', []);

        }else{
        return $this->mainResponse(false, 'لقد قمت بتقييم هذة المقالة سابقا', [], [], 422);

        }



        return $this->mainResponse(false, 'حدث خطأ ما', [], [], 422);
    }
}
