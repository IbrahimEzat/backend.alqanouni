<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryDiscussion;
use App\Models\CommentOpinionDiscussion;
use App\Models\CommentOpinionPoints;
use App\Models\ReplyUserPoint;
use App\Models\Discussion;
use App\Models\DiscussionBestOpinion;
use App\Models\DiscussionOpinionCount;
use App\Models\DiscussionsPionts;
use App\Models\DiscussionStars;
use App\Models\DiscussionUserPoints;
use App\Models\DiscussionViews;
use App\Models\DiscussionWishlist;
use App\Models\OpinionDiscussion;
use App\Models\OpinionDiscussionPoints;
use App\Models\OpinionUserRate;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DiscussionController extends Controller
{
    use GeneralTrait;
    public function index(Request $request)
    {
        $discussions = Discussion::with('user:id,name')->get();
        return $this->mainResponse(true, 'كل المناقسات ', $discussions, []);
    }
    public function show(Request $request)
    {
        $discussion = Discussion::where('id', $request->discussion_id)->first();
        return $this->mainResponse(true, ' محتوى المناقشة ', $discussion, []);
    }
    public function getDiscussion(Request $request) //تمام
    {
        $discussion = Discussion::where('id', $request->discussion_id)->with([
            'user:id,name,image,job,points',
            'discussionPoints:discussion_id,count_points',
            'discussionStars:discussion_id,count_stars',
            'discussionViews:count_views,discussion_id',
            'discussionOpinionCount:count_opinions,discussion_id',
        ])->first();
        return $this->mainResponse(true, ' محتوى المناقشة ', $discussion, []);
    }
    public function countDiscussion() //تمام
    {
        $count = CategoryDiscussion::select('discussion_id')->distinct('discussion_id')->count();
        return $this->mainResponse(true, 'عدد المناقشات', $count, []);
    }

    public function search(Request $request) //تمام
    {
        $disscationsSearch = Discussion::where('title', 'like', '%' . $request->search . '%')->with(
            [
                'user:id,name,image',
                // 'blogs.blogCommentCounts:blog_id,blog_comments',
                'discussionPoints:discussion_id,count_points',
                'discussionStars:discussion_id,count_stars',
                'discussionViews:count_views,discussion_id',
                'discussionOpinionCount:count_opinions,discussion_id'
            ]
        )->get();
        return $this->mainResponse(true, '', $disscationsSearch, []);
    }

    public function getDiscussionsInCategory(Request $request)
    {
        if (!($request->has('slug') && $request->slug))
            return $this->mainResponse(false, 'slug false', [], []);
        $category = Category::where('slug', $request->slug)->first(['id']);
        if (!$category)
            return $this->mainResponse(false, 'cant found category', [], []);
        $category = Category::where('id', $category->id)->with([
            'discussions' => function ($query) {
            },
            'discussions.user:id,name,image',
            'discussions.discussionPoints:discussion_id,count_points',
            'discussions.discussionStars:discussion_id,count_stars',
            'discussions.discussionViews:count_views,discussion_id',
            'discussions.discussionOpinionCount:count_opinions,discussion_id',
        ])->first();
        return $this->mainResponse(true, 'المقالات بنجاح', $category, []);
    }

    public function addDiscussion(Request $request) //تمام
    {
        DB::beginTransaction();
        $discussionValidate = [
            'title' => ['required', 'string', 'unique:blogs,title'],
            'content' => ['required', 'string'],
            'token' => ['required'],
            'category_ids' => ['required'],
        ];
        $validator = Validator::make($request->all(), $discussionValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'unique' => 'هذا العنوان موجود مسبقا',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        try {
            $discussion = Discussion::create([
                'title' => $request->title,
                'user_id' => $request->token['user_id'],
                'content' => $request->content,
                'slug' => Str::slug($request->title, 'UTF-8'),
            ]);

            DiscussionViews::create([
                'discussion_id' => $discussion->id,
                'count_views' => 0
            ]);
            DiscussionsPionts::create([
                'discussion_id' => $discussion->id,
                'count_points' => 0
            ]);
            DiscussionStars::create([
                'discussion_id' => $discussion->id,
                'count_stars' => 0
            ]);
            DiscussionOpinionCount::create([
                'discussion_id' => $discussion->id,
                'count_opinions' => 0
            ]);


            $userDiscussion = User::where('id', $discussion->user_id)->first();

            $discussionsCount = Discussion::whereNotIn('user_id', $this->getAdmins())->count();


            if ($discussionsCount == 1) {
                $userDiscussion->update([
                    'points' => $userDiscussion->points +  3000
                ]);
            } else {
                $userDiscussion->update([
                    'points' => $userDiscussion->points +  5
                ]);
            }
            $user = User::where('id', $request->token['user_id'])->first(['id', 'image', 'name']);

            for ($i = 0; $i < count($request->category_ids); $i++) {
                CategoryDiscussion::create([
                    'discussion_id' => $discussion->id,
                    'category_id' => $request->category_ids[$i]
                ]);
            }

            $dataNotify = [
                'avatar' => $user->image,
                'message' => 'قام ' . ' ' . $user->name . ' باضافة مناقشة جديدة ',
                'url' => '/admin/discussions/' . $discussion->id,
            ];

            DB::commit();

            return $this->mainResponse(true, 'تمت اضافة المناقشة بنجاح', $dataNotify, []);
        } catch (Throwable $e) {
            // throw $e;
            DB::rollback();
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
        }
    }

    public function acceptDiscussion(Request $request)
    {
        $discussion = Discussion::where('id', $request->discussion_id)->first();
        if ($discussion) {

            $discussion->update([
                'status' => 'active',
            ]);
            $user = User::where('id', $discussion->user_id)->first();
            // $userPoints = ;
            $discussionsCount = Discussion::where('status', 'active')->whereNotIn('user_id', $this->getAdmins())->count();

            if ($discussionsCount == 1) {
                $user->update([
                    'points' => $user->points +  3000
                ]);
            } else {
                $user->update([
                    'points' => $user->points +  5
                ]);
            }
            // $user = User::where('id', $request->user_id)->first(['id', 'image', 'name']);
            $dataNotify = [
                'userNotifyId' => $discussion->user_id,
                'avatar' => '',
                'message' => ' قام أدمن بقبول المناقشة الخاصة بك والتي بعنوان  ' . $discussion->title,
                'url' => '/discussions/view/' . $discussion->id,
            ];

            return $this->mainResponse(true, 'تم قبول المناقشة', $dataNotify, []);

            // return $this->mainResponse(true, 'تم قبول المناقشة', $discussion, []);
        }
        return $this->mainResponse(false, 'حدث خطا ما', [], []);
    }
    public function updateDiscussion(Request $request)/* تعديل التصنيف */
    {
        $discussionValidate = [
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
            // 'user_id' => ['required'],
            'discussion_id' => ['required'],

        ];
        $validator = Validator::make($request->all(), $discussionValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'unique' => 'هذا العنوان موجود مسبقا',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $discussion = Discussion::where('id', $request->discussion_id)->first();
        // $user_update = User::where('id', $request->user_id)->first();

        if ($discussion) {
            // if ($discussion->user_id == $request->user_id || $user_update->type == 'admin') {
            $discussion->update([
                'title' => $request->title,
                'content' => $request->content,
                'slug' => Str::slug($request->title),
            ]);
            $blogCats = CategoryDiscussion::where('discussion_id', $request->discussion_id)->delete();
            for ($i = 0; $i < count($request->category_ids); $i++) {
                CategoryDiscussion::create(['discussion_id' => $request->discussion_id, 'category_id' => $request->category_ids[$i]]);
            }
            return $this->mainResponse(true, 'تم التعديل بنجاح', $discussion, []);
            // } else {
            //     return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
            // }
        } else {
            return $this->mainResponse(false, 'لا يمكنك اجراء هذة العملية', [], [], 422);
        }
    }
    public function deleteDiscussion(Request $request)
    {
        $discussionValidate = [
            'discussion_id' => ['required'],
            'reason_delete' => ['required'],
        ];
        $validator = Validator::make($request->all(), $discussionValidate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $discussion = Discussion::where('id', $request->discussion_id)->first();
        // $user_delete = User::where('id', $request->token['user_id'])->first();

        if ($discussion) {
            $discussion->delete();

            $user = User::where('id', $discussion->user_id)->first(['id', 'image', 'name']);
            // $dataNotify = [
            //     'user' => $user,
            //     'message' => ' قام الأدمن بحذف المناقشة الخاصة بك والتي بعنوان' . $discussion->title . 'بسبب ' . $request->reason_delete
            // ];
            $dataNotify = [
                'userNotifyId' => $discussion->user_id,
                'avatar' => '',
                'message' => ' قام الأدمن بحذف المناقشة الخاصة بك والتي بعنوان  '  . ' ' . $discussion->title . ' بسبب ' . $request->reason_delete,
                'url' => ''
            ];
            return $this->mainResponse(true, 'تم حذف المناقشة بنجاح', $dataNotify, []);
        } else {
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
        }
    }

    public function addWishlist(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $discussionWishlist = DiscussionWishlist::where([
            'discussion_id' => $request->discussion_id,
            'user_id' => $request->token['user_id']
        ])->first();

        if (!$discussionWishlist) {
            $discussionWishlist = DiscussionWishlist::create([
                'discussion_id' => $request->discussion_id,
                'user_id' => $request->token['user_id']
            ]);

            $discussionStars = DiscussionStars::where('discussion_id', $request->discussion_id)->first();
            $discussionStars->update([
                'count_stars' => $discussionStars->count_stars  + 1
            ]);
            return $this->mainResponse(true, 'تم اضافة المناقشة للمفضلة ', true, []);
        } else {
            $discussionWishlist->delete();
            $discussionStars = DiscussionStars::where('discussion_id', $request->discussion_id)->first();
            $discussionStars->update([
                'count_stars' => $discussionStars->count_stars - 1
            ]);
            return $this->mainResponse(true, 'تم ازالة المناقشة من لمفضلة ', false, []);
        }
    }

    public function checkWishList(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
            'token' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $wishList = DiscussionWishlist::where(['discussion_id' => $request->discussion_id, 'user_id' => $request->token['user_id']])->first();
        if ($wishList) {
            return $this->mainResponse(true, 'تم اضفافة المناقشة الى المقضلة', true, []);
        } else {
            return $this->mainResponse(false, 'المقالة مضافة للمفضلة ', false, []);
        }
    }


    public function addviewDiscussion(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $discussionview = DiscussionViews::where('discussion_id', $request->discussion_id)->first();
        if (!$discussionview) {
            $discussionview = DiscussionViews::create([
                'discussion_id' => $request->discussion_id,
                'count_views' => 1
            ]);
        } else {

            $discussionview->update([
                'count_views' => $discussionview->count_views + 1
            ]);
        }
        return $this->mainResponse(true, 'تمت العملية بنجاح', $discussionview, []);
    }

    public function minusPointDiscussion(Request $request) //تمام
    {

        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $discussionUserPoints = DiscussionUserPoints::where([
            'discussion_id' => $request->discussion_id,
            'user_id' => $request->token['user_id']
        ])->first();
        $discussionPint = DiscussionsPionts::where('discussion_id', $request->discussion_id)->first();

        if (!$discussionUserPoints) {
            $discussionUserPoints = DiscussionUserPoints::create([
                'discussion_id' => $request->discussion_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'minus'
            ]);
            $discussionPint->update([
                'count_points' => $discussionPint->count_points - 1
            ]);

            return $this->mainResponse(true, 'تم خصم التقييم بنجاح',  $discussionPint->count_points, []);
        } else {
            if($discussionUserPoints->rateType == 'minus')
                return $this->mainResponse(false, 'لا يمكنك خصم المزيد', $discussionPint->count_points, []);
            if ($discussionUserPoints->rateType == 'mid') {
                $discussionUserPoints->update([
                    'rateType' => 'minus',
                ]);
            } else if ($discussionUserPoints->rateType == 'add') {
                $discussionUserPoints->update([
                    'rateType' => 'mid',
                ]);
                $user = User::where('id', $request->token['user_id'])->first();
                // update user points
                $user->update([
                    'points' => --$user->points
                ]);
            }
            $discussionPint->update([
                'count_points' => $discussionPint->count_points - 1
            ]);
            return $this->mainResponse(true, 'تم خصم التقييم بنجاح',  $discussionPint->count_points, []);

        }
    }

    public function addPointDiscussion(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $discussionUserPoints = DiscussionUserPoints::where([
            'discussion_id' => $request->discussion_id,
            'user_id' => $request->token['user_id']
        ])->first();
        $discssion = Discussion::where('id', $request->discussion_id)->first();
        $user = User::where('id', $discssion->user_id)->first();
        $discussionPoint = DiscussionsPionts::where('discussion_id', $request->discussion_id)->first();

        if (!$discussionUserPoints) {
            $discussionUserPoints = DiscussionUserPoints::create([
                'discussion_id' => $request->discussion_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'add'
            ]);
            $discussionPoint->update([
                'count_points' => $discussionPoint->count_points + 1
            ]);
            $user->update([
                'points' => $user->points + 1,
            ]);
            return $this->mainResponse(true, 'تمت إضافة التقييم بنجاح', $discussionPoint->count_points, []);
        } else {
            if($discussionUserPoints->rateType == 'add')
                return $this->mainResponse(false, 'لا يمكنك إضافة المزيد', $discussionPoint->count_points, []);

            if ($discussionUserPoints->rateType == 'mid') {
                $discussionUserPoints->update([
                    'rateType' => 'add',
                ]);
                $user->update([
                    'points' => $user->points + 1,
                ]);
            } else if ($discussionUserPoints->rateType == 'minus') {
                $discussionUserPoints->update([
                    'rateType' => 'mid'
                ]);
            }

            $discussionPoint->update([
                'count_points' => $discussionPoint->count_points + 1
            ]);
            return $this->mainResponse(true, 'تمت إضافة التقييم بنجاح', $discussionPoint->count_points, []);
        }
    }


    public function getAllOpinionsDiscussion(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $opinions = OpinionDiscussion::where('discussion_id', $request->discussion_id)
            ->with([
                'opinionPoints:opinion_discussion_id,count_points',
                'comments.user:name,id,image',
                'comments.CommentPoint',
                'user:id,name,image'
            ])->get();
        if ($opinions)
            return $this->mainResponse(true, 'كل الاراء لهذاة الناقشة  ', $opinions, []);
    }

    public function addOpinionDiscussion(Request $request) //تمام
    {
        $opinionValidate = [
            'content' => ['required', 'string'],
            'token' => ['required'],
            'discussion_id' => ['required'],
        ];
        $validator = Validator::make($request->all(), $opinionValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        DB::beginTransaction();
        try {
            $discussion = Discussion::where('id', $request->discussion_id)->first();
            if ($discussion->user_id != $request->token['user_id']) {
                $opinion = OpinionDiscussion::create([
                    'content' => $request->content,
                    'user_id' => $request->token['user_id'],
                    'discussion_id' => $request->discussion_id,
                ]);

                $discussionOpinionCount = DiscussionOpinionCount::where('discussion_id', $request->discussion_id)->first();
                $discussionOpinionCount->update([
                    'count_opinions' => $discussionOpinionCount->count_opinions + 1
                ]);
                OpinionDiscussionPoints::create([
                    'opinion_discussion_id' => $opinion->id,
                    'count_points' => 0,
                ]);

                $opinionsCount = OpinionDiscussion::whereNotIn('user_id', $this->getAdmins())->count();
                $user = User::where('id', $request->token['user_id'])->first();
                if ($opinionsCount == 1) {
                    $user->update([
                        'points' => $user->points + 2000,
                    ]);
                } else {
                    $user->update([
                        'points' => $user->points + 3,
                    ]);
                }

                $dataNotify = [
                    'userNotifyId' => $discussion->user_id,
                    'avatar' => $user->image,
                    'message' => ' قام ' . $user->name . ' بإضافة رأي على المناقشة الخاصة بك والتي بعنوان  ' . ' '  . $discussion->title,
                    'url' =>  '/discussions/view/' . $discussion->id
                ];
                DB::commit();

                return $this->mainResponse(true, 'تمت اضافة الرأي بنجاح', [
                    'opinion' => $opinion->load(['user', 'opinionPoints', 'comments']),
                    'dataNotify' => $dataNotify
                ], []);
            } else {
                return $this->mainResponse(false, 'لا يمكنك اعطاء رايك على مقالتك  ', [], [], 422);
            }
        } catch (Throwable $e) {
            // throw $e;
            DB::rollback();
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية ', [], [], 422);
        }
    }

    //تمام
    public function deleteOpinionDiscussion(Request $request)
    {
        $discussionValidate = [
            'token' => ['required'],
            'opinion_discussion_id' => ['required'],

        ];
        $validator = Validator::make($request->all(), $discussionValidate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $opinion = OpinionDiscussion::where('id', $request->opinion_discussion_id)->first();
        $user_delete = User::where('id', $request->token['user_id'])->first();

        if ($opinion) {
            if ($opinion->user_id == $request->token['user_id'] || $user_delete->type == 'admin') {
                $opinion->delete();
                $discussionOpinionCount = DiscussionOpinionCount::where('discussion_id', $opinion->discussion_id)->first();
                $discussionOpinionCount->update([
                    'count_opinions' => $discussionOpinionCount->count_opinions - 1
                ]);
                return $this->mainResponse(true, 'تم حذف الرأي بنجاح', $opinion, []);
            } else {
                return $this->mainResponse(false, 'لا يمكنك اجراء هذة العملية', [], [], 422);
            }
        } else {
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
        }
    }

    public function getAllCommentOpinionDiscussion(Request $request)
    {
        $commentOpinionValidate = [
            'content' => ['required', 'string'],
            'user_id' => ['required'],
            'opinion_id' => ['required'],
        ];
        $validator = Validator::make($request->all(), $commentOpinionValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
        ]);
        $comments = CommentOpinionDiscussion::where('opinion_discussion_id', $request->opinion_id)
            ->with(['user:id,image,name', 'CommentPoint'])->get();
        if ($comments) {

            return $this->mainResponse(true, 'كل الردود لهذا الرأي  ', $comments, []);
        } else {
            return $this->mainResponse(false, 'لا يمكنك التعليق على رأيك', [], [], 422);
        }
    }

    //تمام
    public function addCommentOpinionDiscussion(Request $request)
    {
        $commentOpinionValidate = [
            'content' => ['required', 'string'],
            'token' => ['required'],
            'opinion_id' => ['required'],
        ];
        $validator = Validator::make($request->all(), $commentOpinionValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $opinion_userId = OpinionDiscussion::where('id', $request->opinion_id)->first('user_id')->user_id;
        $user = User::where('id', $request->token['user_id'])->first();

        if ($opinion_userId != $request->token['user_id']) {

            DB::beginTransaction();
            try {
                $comment = CommentOpinionDiscussion::create([
                    'content' => $request->content,
                    'user_id' => $request->token['user_id'],
                    'opinion_discussion_id' => $request->opinion_id,
                ]);
                if ($comment) {
                    CommentOpinionPoints::create([
                        'comment_opinion_discussion_id' => $comment->id,
                        'count_points' => 0
                    ]);
                    $commentCount = CommentOpinionDiscussion::whereNotIn('user_id', $this->getAdmins())->count();
                    $user = User::where('id', $request->token['user_id'])->first();
                    if ($commentCount == 1) {
                        $user->update([
                            'points' => $user->points + 1000,
                        ]);
                    } else {
                        $user->update([
                            'points' => $user->points + 1,
                        ]);
                    }
                    DB::commit();
                    return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', ['comment' => $comment, 'user' => $user], []);
                }
            } catch (\Exception $e) {
                DB::rollback();
                return $this->mainResponse(false, 'حدث خطأ أثناء عملية ', [], [], 422);
                // something went wrong
            }
        } else {
            return $this->mainResponse(false, 'لا يمكنك التعليق على رأيك', [], [], 422);
        }
    }


    public function setBestOpinion(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'opinion_id' => 'required|exists:opinion_discussions,id',
            'discussion_id' => 'required|exists:discussions,id',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $best = DiscussionBestOpinion::where('discussion_id', $request->discussion_id)->first();
        $discussion = Discussion::where('id', $request->discussion_id)->first();
        if ($discussion->user_id == $request->token['user_id']) {
            if ($best) {
                $best->update([
                    'opinion_discussion_id' => $request->opinion_id,
                ]);
                return $this->mainResponse(true, 'تم اختيار هذا الرأي كأفضل رأي', []);
            } else {
                DiscussionBestOpinion::create([
                    'opinion_discussion_id' => $request->opinion_id,
                    'discussion_id' => $request->discussion_id,
                ]);
                return $this->mainResponse(true, 'تم اختيار هذا الرأي كأفضل رأي', []);
            }
        } else {
            return $this->mainResponse(false, 'هذة العملية خارج صلاحيتك', []);
        }

        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', []);
    }
    public function checkBestOpinion(Request $request) //تمام
    {
        $validator = Validator::make($request->all(), [
            'discussion_id' => 'required|exists:discussions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $best = DiscussionBestOpinion::where('discussion_id', $request->discussion_id)->first();
        if ($best) {
            return $this->mainResponse(true, '', $best->opinion_discussion_id, []);
        }
        $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', []);
    }

    //تمام
    public function addPointOpinionDiscussion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'opinion_discussion_id' => 'required|exists:opinion_discussions,id',
            'token' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $opinionUserRate = OpinionUserRate::where([
            'opinion_discussion_id' => $request->opinion_discussion_id,
            'user_id' => $request->token['user_id']
        ])->first();
        $opinionPoint = OpinionDiscussionPoints::where('opinion_discussion_id', $request->opinion_discussion_id)->first();
        $opinion = OpinionDiscussion::where('id', $request->opinion_discussion_id)->first();
        $user = User::where('id', $opinion->user_id)->first();

        if (!$opinionUserRate) {
            OpinionUserRate::create([
                'opinion_discussion_id' => $request->opinion_discussion_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'add'
            ]);
            $opinionPoint->update([
                'count_points' => $opinionPoint->count_points + 1
            ]);

            $user->update([
                'points' => $user->points + 1,
            ]);
            return $this->mainResponse(true, 'تم إضافة التقييم بنجاح', $opinionPoint->count_points, []);
        } else {
            if($opinionUserRate->rateType == 'add')
                return $this->mainResponse(false, 'لا يمكنك إضافة المزيد', $opinionPoint->count_points, []);

            if($opinionUserRate->rateType == 'mid') {
                $opinionUserRate->update([
                    'rateType' => 'add',
                ]);
                $user->update([
                    'points' => $user->points + 1,
                ]);
            } else if ($opinionUserRate->rateType == 'minus') {
                $opinionUserRate->update([
                    'rateType' => 'mid'
                ]);
            }
            $opinionPoint->update([
                'count_points' => $opinionPoint->count_points + 1
            ]);
            return $this->mainResponse(true, 'تم إضافة التقييم بنجاح', $opinionPoint->count_points, []);
        }
    }
    //تمام
    public function muinsePointOpinionDiscussion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'opinion_discussion_id' => 'required|exists:opinion_discussions,id',
            'token' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $opinionUserRate = OpinionUserRate::where([
            'opinion_discussion_id' => $request->opinion_discussion_id,
            'user_id' => $request->token['user_id']
        ])->first();
        // return $this->mainResponse(true, 'تمت العملية بنجاح', $opinionUserRate, []);
        $opinionPoint = OpinionDiscussionPoints::where('opinion_discussion_id', $request->opinion_discussion_id)->first();

        if (!$opinionUserRate) {
            OpinionUserRate::create([
                'opinion_discussion_id' => $request->opinion_discussion_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'minus'
            ]);
            $opinionPoint->update([
                'count_points' => $opinionPoint->count_points - 1
            ]);
            return $this->mainResponse(true, 'تم خصم التقييم بنجاح', $opinionPoint->count_points, []);
        } else {
            if($opinionUserRate->rateType == 'minus')
                return $this->mainResponse(false, 'لا يمكنك خصم المزيد', $opinionPoint->count_points, []);

            if ($opinionUserRate->rateType == 'mid') {
                $opinionUserRate->update([
                    'rateType' => 'minus',
                ]);
            } else if ($opinionUserRate->rateType == 'add') {
                $opinionUserRate->update([
                    'rateType' => 'mid',
                ]);
                $user = User::where('id', $request->token['user_id'])->first();
                // update user points
                $user->update([
                    'points' => --$user->points
                ]);
            }
            $opinionPoint->update([
                'count_points' => $opinionPoint->count_points - 1
            ]);
            return $this->mainResponse(true, 'تم خصم التقييم بنجاح', $opinionPoint->count_points, []);

        }
    }
    //تمام
    public function addPointCommentOpinionDiscussion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:comment_opinion_discussions,id',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $commentUserPoints = ReplyUserPoint::where(['comment_opinion_discussion_id' =>
        $request->comment_id, 'user_id' => $request->token['user_id']])->first();
        $commentPoint = CommentOpinionPoints::where('comment_opinion_discussion_id', $request->comment_id)->first();

        if (!$commentUserPoints) {
            $commentUserPoints = ReplyUserPoint::create([
                'comment_opinion_discussion_id' => $request->comment_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'add'
            ]);
            // return $this->mainResponse(true, 'تمت العملية بنجاح', $commentUserPoints, []);

            $commentPoint->update([
                'count_points' => $commentPoint->count_points + 1
            ]);
            return $this->mainResponse(true, 'تمت العملية بنجاح',$commentPoint->count_points, []);
        } else {
            if($commentUserPoints->rateType == 'add')
                return $this->mainResponse(false, 'لا يمكنك إضافة المزيد', $commentPoint->count_points, []);

            if($commentUserPoints->rateType == 'mid') {
                $commentUserPoints->update([
                    'rateType' => 'add'
                ]);
            } else if ($commentUserPoints->rateType == 'minus') {
                $commentUserPoints->update([
                    'rateType' => 'mid'
                ]);
            }
            $commentPoint->update([
                'count_points' => $commentPoint->count_points + 1
            ]);
            return $this->mainResponse(true, 'تمت العملية بنجاح',$commentPoint->count_points, []);
        }
    }
    //تمام
    public function muinseCommentPointOpinionDiscussion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:comment_opinion_discussions,id',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $commentUserPoints = ReplyUserPoint::where(['comment_opinion_discussion_id' =>
        $request->comment_id, 'user_id' => $request->token['user_id']])->first();
        $commentPoint = CommentOpinionPoints::where('comment_opinion_discussion_id', $request->comment_id)->first();

        if (!$commentUserPoints) {
            $commentUserPoints = ReplyUserPoint::create([
                'comment_opinion_discussion_id' => $request->comment_id,
                'user_id' => $request->token['user_id'],
                'rateType' => 'minus'
            ]);
            // return $this->mainResponse(true, 'تمت العملية بنجاح', $commentUserPoints, []);

            $commentPoint->update([
                'count_points' => $commentPoint->count_points - 1
            ]);
            return $this->mainResponse(true, 'تمت العملية بنجاح',$commentPoint->count_points, []);
        } else {
            if($commentUserPoints->rateType == 'minus')
                return $this->mainResponse(false, 'لا يمكنك خصم المزيد', $commentPoint->count_points, []);

            if($commentUserPoints->rateType == 'mid') {
                $commentUserPoints->update([
                    'rateType' => 'minus'
                ]);
            } else if ($commentUserPoints->rateType == 'add') {
                $commentUserPoints->update([
                    'rateType' => 'mid'
                ]);
            }
            $commentPoint->update([
                'count_points' => $commentPoint->count_points - 1
            ]);
            return $this->mainResponse(true, 'تمت العملية بنجاح',$commentPoint->count_points, []);

        }
    }

    public function getCategoriesDiscussion(Request $request)
    {
        $cat_ids = [];
        $category = CategoryDiscussion::where('discussion_id', $request->discussion_id)->get('category_id');
        foreach ($category as $cat) {
            array_push($cat_ids, $cat->category_id);
        }
        return $this->mainResponse(true, '', $cat_ids, []);
    }

    private function getAdmins()
    {
        $admins = User::where('type', 'admin')->get('id');
        $adminsID = [];
        foreach ($admins as $admin) {
            array_push($adminsID, $admin->id);
        }

        return $adminsID;
    }
}
