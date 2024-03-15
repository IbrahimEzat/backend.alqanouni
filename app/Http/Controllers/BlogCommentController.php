<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\User;
use App\Models\BlogComment;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\BlogCommentCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BlogCommentController extends Controller
{
    //
    use GeneralTrait;
    public function index(Request $request)
    {
        $getId = Blog::where('slug', $request->slug)->first();
        $comments = BlogComment::where('blog_id', $getId->id)->get();
        return $this->mainResponse(true, 'comment', $comments, []);
    }
    public function addComment(Request $request)
    {
        $blogCommentValidate = [
            'comment' => ['required', 'string'],
        ];
        $validator = Validator::make($request->all(), $blogCommentValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'exists' => 'يرجى التحقق من البيانات المدخلة',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $blog = Blog::where('id', $request->blog_id)->first();
        if ($blog) {
            DB::beginTransaction();
            try {
                $user = User::where('id', $request->token['user_id'])->first();
                $owner = User::where('id', $blog->user_id)->first();

                $user_type = $user->type;
                $userPoints = $user->points;
                $ownerPoints = $owner->points;
                $blogCommentCount = BlogCommentCount::where('blog_id', $request->blog_id)->first();
                $commentsCount = $blogCommentCount->blog_comments;

                if ($owner->id == $request->token['user_id']) {
                    return $this->mainResponse(false, 'لا يمكنك التعليق على مقالتك', [], [], 422);
                }
                if ($user_type == 'admin') {
                    $adminComments = BlogComment::where('user_id', $user->id)->first();
                    // admin not comment before that
                    if (!$adminComments) {
                        $firstAdminComment = $this->createComment($request);
                        if ($firstAdminComment) {
                            $owner->update([
                                'points' => $ownerPoints + 3,
                            ]);
                            $blogCommentCount->update([
                                'blog_comments' => ++$commentsCount
                            ]);
                            DB::commit();
                            return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', $firstAdminComment);
                        }
                        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
                    } else {
                        $newAdminComment = $this->createComment($request);
                        if ($newAdminComment) {
                            $blogCommentCount->update([
                                'blog_comments' => ++$commentsCount
                            ]);
                            DB::commit();
                            return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', $newAdminComment);
                        }
                        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
                    }
                } else {
                    // check for first comment in website
                    $usersCommentsCount = BlogComment::where('user_id', '!=', 1)->count();
                    if ($usersCommentsCount == 0) {
                        $firstComment = $this->createComment($request);
                        if ($firstComment) {
                            $user->update([
                                'points' => $userPoints + 600,
                            ]);
                            if ($owner->type != 'admin') {
                                $owner->update([
                                    'points' => $ownerPoints + 3,
                                ]);
                            }
                            $blogCommentCount->update([
                                'blog_comments' => ++$commentsCount
                            ]);
                            DB::commit();
                            return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', $firstComment);
                        }
                        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
                    } else {
                        $query = BlogComment::where(['user_id' => $user->id, 'blog_id' => $blog->id])->first();
                        if ($query) {
                            $newComment = $this->createComment($request);
                            if ($newComment) {
                                $blogCommentCount->update([
                                    'blog_comments' => ++$commentsCount
                                ]);
                                DB::commit();
                                return $this->mainResponse(true, 'تم إضافة تعليق بنجاح', $newComment);
                            }
                            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
                        } else {
                            $firstUserComment = $this->createComment($request);
                            if ($firstUserComment) {
                                $user->update([
                                    'points' => $userPoints + 1,
                                ]);
                                if ($owner->type != 'admin') {
                                    $owner->update([
                                        'points' => $ownerPoints + 3,
                                    ]);
                                }
                                $blogCommentCount->update([
                                    'blog_comments' => ++$commentsCount
                                ]);
                                DB::commit();
                                return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', $firstUserComment);
                            }
                            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
                        }
                    }
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }
    public function deleteComment(Request $request)
    {
        $delete_blogComment = BlogComment::where('id', $request->comment_id)->first();
        $adminUser = User::where('id', $request->token['user_id'])->first();
        if ($delete_blogComment) {
            if ($delete_blogComment->user_id == $request->token['user_id'] || $adminUser->type == 'admin') {
                $countComment = BlogCommentCount::where('blog_id', $delete_blogComment->blog_id)->first();
                $delete_blogComment->delete();
                if ($countComment) {
                    $count = $countComment->blog_comments;
                    $newCount = $count--;
                    $countComment->update([
                        'blog_comments' => $newCount,
                    ]);
                }
                return $this->mainResponse(true, 'تم حذف التعليق بنجاح', [], []);
            } else {
                return $this->mainResponse(false, 'لا يمكنك حذف هذا التعليق', [], [], 403);
            }
        }

        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }

    private function createComment($request)
    {
        $newComment = BlogComment::create([
            'comment' => $request->comment,
            'blog_id' => $request->blog_id,
            'user_id' => $request->token['user_id']
        ]);

        $countComment = BlogCommentCount::where('blog_id', $request->blog_id)->first();
        if ($countComment) {
            $count = $countComment->blog_comments;
            $countComment->update([
                'blog_comments' => $count++,
            ]);
        } else {
            BlogCommentCount::create([
                'blog_id' => $request->blog_id,
                'blog_comments' => 1
            ]);
        }
        $newComment->load('user:id,name,image');
        return $newComment;
    }
}
