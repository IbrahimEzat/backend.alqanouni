<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\User;
use App\Models\Topic;
use App\Models\BlogView;
use App\Models\Category;
use App\Models\WishList;
use App\Models\BlogPoint;
use App\Models\BlogComment;
use Illuminate\Support\Str;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\BlogUserPoint;
use App\Models\BlogCommentCount;
use App\Models\BlogWishListCount;
use Illuminate\Support\Facades\DB;
use App\Events\Customer\BlogCreatedEvent;
use Illuminate\Support\Facades\Validator;
use App\Notifications\Admin\BlogCreatedNotification;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;

class UserBlogController extends Controller
{
    //
    use GeneralTrait;
    public function index()
    {
        $blogs = Blog::all();
        return $this->mainResponse(true, 'blogs', $blogs, []);
    }

        protected function blogsCallBack(string $table, int $targeInfoId, QueryBuilder $query)
    {
        if ($table == 'topic_sections')
            $query->select('section_id')->from($table)->where('topic_id', $targeInfoId);
        else
            $query->select('blog_id')->from($table)->where('category_id', $targeInfoId);
    }

    public function getBolgsInCategiory(Request $request)
    {
        $catInfo = Category::where('slug', $request->slug)->first();
        $sortBy = $request->query('sortBy', 'created_at');
        $orderBy = $request->query('orderBy', 'desc');
        $targetId = 0;
        $tableName = '';
        if (!$catInfo) {
            $topicInfo = Topic::where('slug', $request->slug)->first();
            $targetId = $topicInfo->id;
            $tableName = 'topic_sections';
        } else {
            $targetId = $catInfo->id;
            $tableName = 'blog_categories';
        }
        $blogs = Blog::whereIn('blogs.id', function (QueryBuilder $query) use ($targetId, $tableName) {
            $this->blogsCallBack($tableName, $targetId, $query);
        })->selectRaw('title , blogs.slug , blogs.subtitle , blogs.id , blogs.created_at , CONCAT(?,blogs.image) as blog_image , user_id , blog_comments_count.blog_comments , blog_views.blog_views , blog_points.blog_points , blog_wish_list_counts.count as wishlist_count , users.id as user_id , users.name as username , CONCAT(?,users.image) as user_image', ['https://alqanouni.com/public/uploads/blogs/', 'https://alqanouni.com/public/uploads/user-image/'])
            ->orderBy($sortBy, $orderBy)
            ->join('blog_comments_count', 'blogs.id', '=', 'blog_comments_count.blog_id')
            ->join('blog_views', 'blogs.id', '=', 'blog_views.blog_id')
            ->join('blog_points', 'blogs.id', '=', 'blog_points.blog_id')
            ->join('blog_wish_list_counts', 'blogs.id', '=', 'blog_wish_list_counts.blog_id')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->paginate(3);
        return $this->mainResponse(true, 'blogsData', $blogs, []);
    }

    public function search(Request $request)
    {
        $sortBy = $request->query('sortBy', 'created_at');
        $orderBy = $request->query('orderBy', 'desc');
        $data = Blog::where('title', 'like', '%' . $request->search . '%')
            ->where('status', 'active')
            ->selectRaw('title , blogs.slug , blogs.id , blogs.created_at , CONCAT(?,blogs.image) as blog_image , user_id , blog_comments_count.blog_comments , blog_views.blog_views , blog_points.blog_points , blog_wish_list_counts.count as wishlist_count , users.id as user_id , users.name as username , CONCAT(?,users.image) as user_image', ['https://alqanouni.com/public/uploads/blogs/', 'https://alqanouni.com/public/uploads/user-image/'])
            ->orderBy($sortBy, $orderBy)
            ->join('blog_comments_count', 'blogs.id', '=', 'blog_comments_count.blog_id')
            ->join('blog_views', 'blogs.id', '=', 'blog_views.blog_id')
            ->join('blog_points', 'blogs.id', '=', 'blog_points.blog_id')
            ->join('blog_wish_list_counts', 'blogs.id', '=', 'blog_wish_list_counts.blog_id')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->paginate(3);
        return $this->mainResponse(true, 'correct get info',  $data, []);
    }

    public function count()
    {
        $count = Blog::where('status', 'active')->count();
        return $this->mainResponse(true, 'count', $count, []);
    }

    public function showBlog(Request $request)
    {
        $data = Blog::where('slug', $request->slug)->where('status', 'active')->with([
            'user:id,name,image,job,points',
            'blogCommentCounts:blog_id,blog_comments',
            'blogPoints:blog_id,blog_points',
            'blogWishListCounts:blog_id,count',
            'blogViews:blog_views,blog_id'
        ])->first();
        $comments = BlogComment::where('blog_id', $data->id)->with([
            'user:id,name,image',
        ])->get();
        if ($data) {
            return $this->mainResponse(true, 'result', ['comments' => $comments, 'blog' => $data], []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => 'حدث خطأ ما'], 422);
    }


    public function addBlog(Request $request)
    {
        $blogValidate = [
            'title' => ['required', 'string', 'unique:blogs,title'],
            'content' => ['required', 'string'],
        ];
        $validator = Validator::make($request->all(), $blogValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'unique' => 'هذا العنوان موجود مسبقا',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        DB::beginTransaction();
        try {
            $add_blog = Blog::create([
                'title' => $request->title,
                'content' => $request->content,
                'slug' => $this->arabicSlug($request->title),
                'user_id' => $request->token['user_id']
            ]);
            if ($add_blog) {
                BlogView::create(['blog_id' => $add_blog->id, 'blog_views' => 0]);
                BlogPoint::create(['blog_id' => $add_blog->id, 'blog_points' => 0]);
                BlogCommentCount::create(['blog_id' => $add_blog->id, 'blog_comments' => 0]);
                BlogWishListCount::create(['blog_id' => $add_blog->id, 'count' => 0]);
                $userBlog = User::where('id', $request->token['user_id'])->first(['id', 'image', 'name']);
                $dataNotify = [
                    'avatar' => $userBlog->image,
                    'message' => ' قام ' . $userBlog->name . ' باضافة مقالة جديدة ',
                    'url' =>  '/admin/blogs/' . $add_blog->slug,
                ];
                DB::commit();
                return $this->mainResponse(true, 'تمت اضافة مقالمة بنجاح', $dataNotify, []);
            }
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], ['error' => 'حدث خطأ ما'], 422);
        }
    }
    public function deleteBlog(Request $request)
    {
        $delete_blog = Blog::where('slug', $request->slug)->first();
        if ($delete_blog) {
            if ($delete_blog->user_id == $request->token['user_id']) {
                $delete_blog->delete();
                return $this->mainResponse(true, 'تم حذف المقالة بنجاح', [], []);
            } else {
                return $this->mainResponse(false, 'لا يمكنك حذف هذه المقالة', [], [], 403);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }


    public function increaceView(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $blog = BlogView::where('blog_id', $request->blog_id)->first();

        if ($blog) {
            $blog->update(['blog_views' => $blog->blog_views + 1]);
            $user = User::find($request->token['user_id']);
            if ($user) {
                if ($user->type != 'admin') {
                    $points = $user->points;
                    $user->update(['points' => $points + 1]);
                }
            }
            return $this->mainResponse(true, $blog, 'تم مشاهدة المقالة', []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], [], 422);
    }
    public function increase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $blogUserPoint = BlogUserPoint::where(['blog_id' => $request->blog_id, 'user_id' => $request->user_id])->first();
        $blog = BlogPoint::where('blog_id', $request->blog_id)->first();
        $userBlog = Blog::where('id', $request->blog_id)->first()->user_id;
        $user = User::find($userBlog);


        if (!$blogUserPoint) {
            BlogUserPoint::create([
                'blog_id' => $request->blog_id,
                'user_id' => $request->user_id,
                'rateType' => 'add'
            ]);

            $blog->update(['blog_points' => $blog->blog_points + 1]);

            $user->update([
                'points' => $user->points + 1
            ]);
            return $this->mainResponse(true, 'تم زيادة النقاط بنجاح', $blog->blog_points, []);
        } else {
            if ($blogUserPoint->rateType == 'add')
                return $this->mainResponse(false, 'لا يمكنك إضافة المزيد', $blog->blog_points);

            if ($blogUserPoint->rateType == 'mid') {
                $blogUserPoint->update([
                    'rateType' => 'add'
                ]);
                $user->update([
                    'points' => $user->points + 1,
                ]);
            } else if ($blogUserPoint->rateType == 'minus') {
                $blogUserPoint->update([
                    'rateType' => 'mid'
                ]);
            }
            $blog->update(['blog_points' => $blog->blog_points + 1]);
            return $this->mainResponse(true, 'تم زيادة النقاط بنجاح', $blog->blog_points, []);
        }
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

        $blogUserPoint = BlogUserPoint::where(['blog_id' => $request->blog_id, 'user_id' => $request->user_id])->first();
        $blog = BlogPoint::where('blog_id', $request->blog_id)->first();
        if (!$blogUserPoint) {
            BlogUserPoint::create([
                'blog_id' => $request->blog_id,
                'user_id' => $request->user_id,
                'rateType' => 'minus'
            ]);
            $blog->update(['blog_points' => $blog->blog_points - 1]);
            return $this->mainResponse(true, 'تم خصم النقاط بنجاح', $blog->blog_points, []);
        } else {
            if($blogUserPoint->rateType == 'minus')
                return $this->mainResponse(false, 'لا يمكنك خصم المزيد', $blog->blog_points);

            if ($blogUserPoint->rateType == 'mid') {
                $blogUserPoint->update([
                    'rateType' => 'minus',
                ]);
            } else if ($blogUserPoint->rateType == 'add') {
                $blogUserPoint->update([
                    'rateType' => 'mid',
                ]);
                $user = User::where('id', $blogUserPoint->user_id)->first();
                // update user points
                $user->update([
                    'points' => --$user->points
                ]);
            }
            $blog->update(['blog_points' => $blog->blog_points - 1]);
            return $this->mainResponse(true, 'تم خصم النقاط بنجاح', $blog->blog_points, []);

        }
    }
    public function addBlogWishList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $wishList = WishList::where(['blog_id' => $request->blog_id, 'user_id' => $request->user_id])->first();
        if (!$wishList) {
            $wishList = WishList::create([
                'blog_id' => $request->blog_id,
                'user_id' => $request->user_id,
            ]);
            $wishListCount = BlogWishListCount::where('blog_id', $request->blog_id)->first();
            $wishListCount->update([
                'count' => $wishListCount->count + 1
            ]);
            return $this->mainResponse(true, 'تم اضفافة المقالة الى المقضلة', true, []);
        } else {
            $wishList->delete();

            $wishListCount = BlogWishListCount::where('blog_id', $request->blog_id)->first();
            $wishListCount->update([
                'count' => $wishListCount->count - 1
            ]);
            return $this->mainResponse(true, 'تم ازالة المقالة الى المقضلة', false, []);
        }
    }

    public function checkWishList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $wishList = WishList::where(['blog_id' => $request->blog_id, 'user_id' => $request->user_id])->first();
        if ($wishList) {
            return $this->mainResponse(true, 'تم اضفافة المقالة الى المقضلة', true, []);
        } else {
            return $this->mainResponse(false, 'المقالة مضافة للمفضلة ', false, []);
        }
    }
    
    public function newset()
    {
        $blogs = Blog::with('user:id,name')->select(['id', 'slug', 'title', 'user_id', 'image'])->orderBy('id', 'desc')->take(3)->get();
        return $this->mainResponse(true, 'success', $blogs);
    }

    public function topBlogs()
    {
        $blogs = Blog::selectRaw('title , blogs.slug , blogs.id , user_id , CONCAT(?,blogs.image) as blog_image, blog_views.blog_views, blog_views.updated_at as last_watch , users.id as user_id , users.name as username', ['https://alqanouni.com/public/uploads/blogs/'])
            ->orderBy('last_watch', 'desc')
            ->join('blog_views', 'blogs.id', '=', 'blog_views.blog_id')
            ->join('users', 'blogs.user_id', '=', 'users.id')
            ->take(5)->get();
        return $this->mainResponse(true, 'success', $blogs);
    }

    public function getAllBlogTopics()
    {
        $topics = Topic::where('type', 'blog')->get();
        if ($topics)
            return $this->mainResponse(true, '', $topics);
        return $this->mainResponse(false, 'لا يوجد تصنيفات في الموقع', []);
    }
}
