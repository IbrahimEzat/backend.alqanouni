<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AnswerUserRate;
use App\Models\Category;
use App\Models\Competition;
use App\Models\CompetitionAnswer;
use App\Models\CompetitionAnswerPoint;
use App\Models\CompetitionAnswerPrize;
use App\Models\CompetitionCategory;
use App\Models\CompetitionView;
use App\Models\CompetitionWishlist;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\returnSelf;

class CompetitionController extends Controller
{
    use GeneralTrait;

    // start admin actions
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'prize' => 'required|image',
            'susponsor_image' => 'nullable',
            'content' => 'required',
            'points' => 'required|numeric|max:9999',
            'duration' => 'required|date',
            'category_ids' => 'required'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'image' => 'يرجى ارفاق صورة',
            'numeric' => 'الرجاء ادخال قيمة عدد صحيح',
            'date' => 'الرجاء ادخال قيمة صالحة',
            'required_with' => 'هذا الحقل مطلوب عندما يكون حقل صورة الراعي موجودة',
            'max' => 'أكبر قيمة للنقاط هي 9999'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        DB::beginTransaction();
        try {
            $data['user_id'] = json_decode($request->token)->{'user_id'};
            $data['title'] = $request->title;
            $data['points'] = $request->points;
            $data['duration'] = $request->duration;
            $data['question'] = $request->content;

            if ($prize_image = $request->file('prize')) {
                $image_name = time() . '.' . $prize_image->getClientOriginalExtension();
                $prize_image->move(public_path('uploads/competitions/prizes'), $image_name);
                $data['prize_image'] = $image_name;
            }

            if ($sponsor_image = $request->file('susponsor_image')) {
                $image_name = time() . '.' . $sponsor_image->getClientOriginalExtension();
                $sponsor_image->move(public_path('uploads/competitions/sponsors'), $image_name);
                $data['sponsor_image'] = $image_name;
                $data['sponsor_url'] = $request->susponsor_link;
            }
            $competition = Competition::create($data);
            if ($competition) {

                $ids = explode(',', $request->category_ids);
                foreach ($ids as $key => $val) {
                    CompetitionCategory::create([
                        'competition_id' => $competition->id,
                        'category_id' => $val
                    ]);
                }
                CompetitionView::create([
                    'competition_id' => $competition->id,
                    'view_count' => 0
                ]);
                DB::commit();
                return $this->mainResponse(true, 'تم إنشاء المسابقة بنجاح', $competition, []);
            }
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id',
            'title' => 'required',
            'prize_image' => 'nullable',
            'susponsor_image' => 'nullable',
            'susponsor_link' => 'required_with:susponsor_image',
            'content' => 'required',
            'points' => 'required|numeric|max:9999',
            'duration' => 'required|date',
            'category_ids' => 'required'
        ], [
            'required' => 'هذا الحقل مطلوب',
            'numeric' => 'الرجاء ادخال قيمة عدد صحيح',
            'date' => 'الرجاء ادخال قيمة صالحة',
            'max' => 'أكبر قيمة للنقاط هي 9999',
//            'image' => 'يرجى ارفاق صورة',
            'required_with' => 'هذا الحقل مطلوب عندما يكون حقل صورة الراعي موجودة',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        DB::beginTransaction();
        try {
            $data['title'] = $request->title;
            $data['points'] = $request->points;
            $data['duration'] = $request->duration;
            $data['question'] = $request->content;


            if ($prize_image = $request->file('prize_image')) {
                $image_name = time() . '.' . $prize_image->getClientOriginalExtension();
                $prize_image->move(public_path('uploads/competitions/prizes'), $image_name);
                $data['prize_image'] = $image_name;
            }

            if ($sponsor_image = $request->file('susponsor_image')) {
                $image_name = time() . '.' . $sponsor_image->getClientOriginalExtension();
                $sponsor_image->move(public_path('uploads/competitions/sponsors'), $image_name);
                $data['sponsor_image'] = $image_name;

            }
            $data['sponsor_url'] = $request->susponsor_link;

            $competition = Competition::where('id', $request->competition_id)->first();
            if ($competition) {
                $competition->update($data);
                CompetitionCategory::where('competition_id', $competition->id)->delete();
                $ids = explode(',', $request->category_ids);
                foreach ($ids as $key => $val) {
                    CompetitionCategory::create([
                        'competition_id' => $competition->id,
                        'category_id' => $val
                    ]);
                }
                DB::commit();
                return $this->mainResponse(true, 'تم تعديل المسابقة بنجاح', $competition, []);
            }
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
        }
    }

    public function list()
    {
        $competitions = Competition::orderBy('id', 'desc')->withCount('competitionAnswers')->get(['id', 'title', 'duration', 'status', 'created_at']);
        if ($competitions) {
            return $this->mainResponse(true, 'هذه كل المسابقات في الموقع', $competitions, []);
        }
        return $this->mainResponse(false, 'حدث خطا ما', [], ['error' => ['حدث خطأ ما']]);
    }

    public function competitionInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $competition = Competition::where('id', $request->competition_id)->with('categories')->first();
        if ($competition) {
            return $this->mainResponse(true, 'هذه المسايقة التي طلبتها', $competition, []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        $competition = Competition::where('id', $request->competition_id)->first();
        if ($competition) {
            $competition->delete();
            return $this->mainResponse(true, 'تم حذف المسابقة ينجاح', []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => 'حدث خطأ ما']);
    }

    public function updateDegree(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:competition_answers,id',
            'degree' => 'required|min:1|max:20'
        ], [
            'min' => 'أقل قيمة للعلامة هي 1',
            'max' => 'أكبر قيمة للعلامة هي 20',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $answer = CompetitionAnswer::where('id', $request->answer_id)->first();

        $answer->update([
            'degree' => $request->degree,
        ]);
        return $this->mainResponse(true, 'تم إضافة العلامة بنجاح', $answer->degree);
    }

    public function submitCorrect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        DB::beginTransaction();
        try {

            $competition = Competition::where('id', $request->competition_id)->first();
            $competition->update([
                'is_correct' => true
            ]);
            $firstCorrectCompetition = Competition::where('is_correct', true)->get();

            $firstTopAnswers = CompetitionAnswer::where(
                'competition_id', $competition->id
            )->orderBy('degree', 'desc')->orderBy('created_at', 'asc')->take(3)->get(['id', 'degree', 'user_id']);

           if(count($firstCorrectCompetition) == 1){
               $firstTopAnswers = CompetitionAnswer::where('competition_id', $firstCorrectCompetition[0]->id)
                   ->orderBy('degree', 'desc')->orderBy('created_at', 'asc')->take(3)->get(['id', 'degree', 'user_id']);
               for ($i= 0 ; $i<count($firstTopAnswers) ;$i++){
                   if($i == 0) {
                       $firstUser = User::where('id', $firstTopAnswers[0]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 5000,
                       ]);
                   }else if ($i == 1){
                       $firstUser = User::where('id', $firstTopAnswers[1]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 3000,
                       ]);
                   }else if ($i == 2){
                       $firstUser = User::where('id', $firstTopAnswers[2]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 1000,
                       ]);
                   }
             }
           }else {
               for ($i = 0; $i < count($firstTopAnswers); $i++) {
                   if ($i == 0) {
                       $firstUser = User::where('id', $firstTopAnswers[0]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 1000,
                       ]);
                   } else if ($i == 1) {
                       $firstUser = User::where('id', $firstTopAnswers[1]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 700,
                       ]);
                   } else if ($i == 2) {
                       $firstUser = User::where('id', $firstTopAnswers[2]->user_id)->first();
                       $firstUser->update([
                           'points' => $firstUser->points + 300,
                       ]);
                   }
               }
           }
           $this->addPrize($firstTopAnswers, $competition);

            // top 3 answers ids
            $ids = [];
            foreach ($firstTopAnswers as $answer) {
                array_push($ids, $answer->id);
            }
            // other answers to 10 points for each user success
            $otherAnswers = CompetitionAnswer::where('competition_id', $competition->id)->where('degree', '>', 10)->whereNotIn('id', $ids)->get(['id', 'user_id', 'degree']);
            $otherUsersIds = [];
            foreach ($otherAnswers as $answer){
                array_push($otherUsersIds, $answer->user_id);
            }
            $otherUsers = User::whereIn('id', $otherUsersIds)->get(['id', 'points']);
            foreach ($otherUsers as $user) {
                $user->update([
                    'points' => $user->points + 10
                ]);
            }

            $data = CompetitionAnswer::where('competition_id', $competition->id)->with([
                'user:id,name,image',
                'competitionAnswerPoint:competition_answer_id,answer_points',
                'competitionAnswerPrize'
            ])->orderBy('degree', 'desc')->orderBy('created_at', 'asc')->get();


            // $allUsersIds = CompetitionAnswer::where('competition_id', $competition->id)->get(['id', 'user_id']);
            // $dataNotify = [];
            // foreach ($allUsersIds as $user) {
            //     $dataNotify1 = [
            //         'userNotifyId' => $user->user_id,
            //         'avatar' => '',
            //         'message' => 'تم الانتهاء من تصحيح المسابقة التي بعنوان: ' . $competition->title,
            //         'url' => '/competitions/view/' . $competition->id,
            //     ];
            //     array_push($dataNotify , $dataNotify1);
            // }
            $dataNotifyTop = [];
            for ($i=0; $i<count($firstTopAnswers); $i++) {
                if($i == 0) {
                    $dataNotify1 = [
                        'userNotifyId' => $firstTopAnswers[$i]->user_id,
                        'avatar' => '',
                        'message' => 'مبروك لقد حصلت على جائزة أفضل إجابة في المسابقة التي شاركت فيها التي بعنوان: ' . $competition->title,
                        'url' => '/competitions/view/' . $competition->id,
                    ];
                } else if($i == 1) {
                    $dataNotify1 = [
                        'userNotifyId' => $firstTopAnswers[$i]->user_id,
                        'avatar' => '',
                        'message' => 'مبروك لقد حصلت على جائزة ثاني أفضل إجابة في المسابقة التي شاركت فيها التي بعنوان: ' . $competition->title,
                        'url' => '/competitions/view/' . $competition->id,
                    ];
                } else if($i == 2) {
                    $dataNotify1 = [
                        'userNotifyId' => $firstTopAnswers[$i]->user_id,
                        'avatar' => '',
                        'message' => 'مبروك لقد حصلت على جائزة ثالث أفضل إجابة في المسابقة التي شاركت فيها التي بعنوان: ' . $competition->title,
                        'url' => '/competitions/view/' . $competition->id,
                    ];
                }
                array_push($dataNotifyTop, $dataNotify1);
            }

            DB::commit();
            return $this->mainResponse(true, 'تم إعتماد التصحيح بنجاح',['data'=>$data , 'dataNotifyTop' => $dataNotifyTop]);

        } catch (\Throwable $throwable) {
            DB::rollBack();
            // throw $throwable;
            return $this->mainResponse(false, 'حدث خطأ ما حاول مرة أخرى', [], ['error' => ['حدث خطأ ما حاول مرة أخرى']]);
        }
    }
    // end admin actions

    // start user actions
    public function getAllCompetition(Request $request)
    {
        if (!($request->has('slug') && $request->slug))
            return $this->mainResponse(false, 'slug false', [], []);
        $category = Category::where('slug', $request->slug)->first(['id']);
        if (!$category)
            return $this->mainResponse(false, 'cant found category', [], []);

        $category = Category::where('id', $category->id)->with([
            'competitions.wishlists',
            'competitions:id,title,status,points,duration,created_at,user_id',
            'competitions.user:id,name,image',
            'competitions.competitionView:competition_id,view_count',
        ])->first();

        return $this->mainResponse(true, 'هذه كل المسابقات في التصنيف المختار', $category, []);
    }

    public function count()
    {
        $count = Competition::count();
        return $this->mainResponse(true, '', $count);
    }

    public function increaseView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        $viewCount = CompetitionView::where('competition_id', $request->competition_id)->first();
        if ($viewCount) {
            $viewCount->update([
                'view_count' => ++$viewCount->view_count
            ]);
            return $this->mainResponse(true, 'هذه المسايقة التي طلبتها', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']], 422);
    }

    public function toggleWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        $query = CompetitionWishlist::where([
            'competition_id' => $request->competition_id,
            'user_id' => $request->token['user_id'],
        ])->first();
        if (!$query) {
            $newRecord = CompetitionWishlist::create([
                'competition_id' => $request->competition_id,
                'user_id' => $request->token['user_id'],
            ]);
            if ($newRecord)
                return $this->mainResponse(true, 'تم إضافة المسابقة إلى المفضلة',true, []);
        } else {
            $query->delete();
            return $this->mainResponse(true, 'تم حذف المسابقة من المفضلة',false, []);
        }
    }

    public function checkWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occured', [], $validator->errors()->messages(), 422);
        }

        $query = CompetitionWishlist::where([
            'competition_id' => $request->competition_id,
            'user_id' => $request->token['user_id'],
        ])->first();
        if ($query) {
            return $this->mainResponse(true, 'هذه المسابقة موجودة في المفضلة',true, []);
        } else {
            return $this->mainResponse(false, 'هذه المسابقة غير موجودة في المفضلة',false, []);
        }
    }

    public function competitionCategories()
    {
        $categories = Category::withCount('competitions')->get();
        return $this->mainResponse(true, 'هذه التصنيفات الموجودة في المسابقات', $categories, []);
    }

    public function search(Request $request)
    {
        $data = Competition::where('title', 'like', '%' . $request->search . '%')
            ->with([
                'user:id,name,image',
                'competitionView:competition_id,view_count',
            ])->withCount('wishlists')->get(['id', 'title', 'status', 'points', 'duration', 'created_at', 'user_id']);
        return $this->mainResponse(true, 'correct get info',  $data, []);
    }

    public function addAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id',
            'content' => 'required',
        ], [
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $competition = Competition::where('id', $request->competition_id)->first(['status', 'points', 'duration']);
        if ($competition->status <= 0)
            return $this->mainResponse(false, 'للأسف هذه المسابقة منتهية', [], ['error' => ['للأسف هذه المسابقة منتهية']]);


        $userAnswer = CompetitionAnswer::where([
            'competition_id' => $request->competition_id,
            'user_id' => $request->token['user_id'],
        ])->first();

        if ($userAnswer)
            return $this->mainResponse(false, 'لقد قمت بالإجابة من قبل اذهب إلى المسابقة لتعديل الاجابة', [], ['error' => ['لقد قمت بالإجابة من قبل اذهب إلى المسابقة لتعديل الاجابة']], 422);

        $user = User::where('id', $request->token['user_id'])->first();
        if($user->points < $competition->points)
            return $this->mainResponse(false, 'أنت لا تملك نقاط كافية للمشاركة في هذه المسابقة', [], ['error' => ['أنت لا تملك نقاط كافية للمشاركة في هذه المسابقة']], 422);


        $newUserAnswer = CompetitionAnswer::create([
            'competition_id' => $request->competition_id,
            'user_id' =>  $request->token['user_id'],
            'content' => $request->content,
        ]);
        if ($newUserAnswer) {
            DB::beginTransaction();
            try {
                $user->update([
                    'points' => $user->points - $competition->points
                ]);
                CompetitionAnswerPoint::create([
                    'competition_answer_id' => $newUserAnswer->id,
                ]);
                DB::commit();
                return $this->mainResponse(true, 'تم إضافة الإجابة بنجاح', $newUserAnswer);
            } catch (\Throwable $th) {
                DB::rollBack();
                // throw $th;
                return $this->mainResponse(false, 'حدث خطأ ما حاول مرة أخرى', [], ['error' => ['حدث خطأ ما حاول مرة أخرى']]);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']]);
    }

    public function updateAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id',
                'answer_id' => 'required|exists:competition_answers,id',
            'content' => 'required',
        ], [
            'required' => 'هذا الحقل مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $competition = Competition::where('id', $request->competition_id)->first(['status', 'duration']);
        if ($competition->status <= 0)
            return $this->mainResponse(false, 'للأسف هذه المسابقة منتهية', [], ['error' => ['للأسف هذه المسابقة منتهية']]);

        $userAnswer = CompetitionAnswer::where([
            'id' => $request->answer_id,
        ])->first();

        if ($userAnswer) {
            DB::beginTransaction();
            try {
                $updatedAnswer = $userAnswer->update([
                    'content' => $request->content,
                ]);

                DB::commit();
                return $this->mainResponse(true, 'تم تعديل إجابتك بنجاح', $userAnswer);
            } catch (\Throwable $th) {
                DB::rollBack();
                // throw $th;
                return $this->mainResponse(false, 'حدث خطأ ما حاول مرة أخرى', [], ['error' => ['حدث خطأ ما حاول مرة أخرى']]);

            }
        }
        return $this->mainResponse(false, 'حدث خطأ ما', [], ['error' => ['حدث خطأ ما']]);
    }

    public function increasePoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:competition_answers,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $userRate = AnswerUserRate::where([
            'competition_answer_id' => $request->answer_id,
            'user_id' => $request->token['user_id'],
        ])->first();
        $query = CompetitionAnswerPoint::where('competition_answer_id', $request->answer_id)->first();
        $user = User::where(
            'id', $request->token['user_id']
        )->first();

        // rate for first time on this answer
        if (!$userRate) {
            AnswerUserRate::create([
                'competition_answer_id' => $request->answer_id,
                'user_id' => $request->token['user_id'],
                'rate' => 'add'
            ]);
            $query->update([
                'answer_points' => ++$query->answer_points
            ]);
            // update user points ++
            $user->update([
                'points' => ++$user->points
            ]);
            return $this->mainResponse(true, 'تم إضافة التقييم بنجاح', $query->answer_points, []);
        }
        // rate befor that
        else {
            // check prev status
            if ($userRate->rate == 'add')
                return $this->mainResponse(false, 'لا يمكنك إضافة المزيد', $query->answer_points);

            if ($userRate->rate == 'mid') {
                $userRate->update([
                    'rate' => 'add',
                ]);
                // update user points ++
                $user->update([
                    'points' => ++$user->points
                ]);
            } else if ($userRate->rate == 'minus') {
                $userRate->update([
                    'rate' => 'mid',
                ]);
            }
            $query->update([
                'answer_points' => ++$query->answer_points
            ]);
            return $this->mainResponse(true, 'تم إضافة التقييم بنجاح', $query->answer_points, []);
        }
    }

    public function decreasePoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:competition_answers,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $userRate = AnswerUserRate::where([
            'competition_answer_id' => $request->answer_id,
            'user_id' => $request->token['user_id'],
        ])->first();
        $query = CompetitionAnswerPoint::where('competition_answer_id', $request->answer_id)->first();
        $user = User::where(
            'id',
            $request->token['user_id']
        )->first();

        // rate for first time on this answer
        if (!$userRate) {
            AnswerUserRate::create([
                'competition_answer_id' => $request->answer_id,
                'user_id' => $request->token['user_id'],
                'rate' => 'minus'
            ]);
            $query->update([
                'answer_points' => --$query->answer_points
            ]);
            return $this->mainResponse(true, 'تم خصم التقييم بنجاح', $query->answer_points, []);
        }
        // rate before that
        else {
            // check prev status
            if ($userRate->rate == 'minus')
                return $this->mainResponse(false, 'لا يمكنك خصم المزيد', $query->answer_points);

            if ($userRate->rate == 'mid') {
                $userRate->update([
                    'rate' => 'minus',
                ]);
            } else if ($userRate->rate == 'add') {
                $userRate->update([
                    'rate' => 'mid',
                ]);
                // update user points
                $user->update([
                    'points' => --$user->points
                ]);
            }
            $query->update([
                'answer_points' => --$query->answer_points
            ]);
            return $this->mainResponse(true, 'تم خصم التقييم بنجاح', $query->answer_points, []);
        }
    }

    public function competitionDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|exists:competitions,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $competition = Competition::where('id', $request->competition_id)->first();
        $data = [];
        $auth = User::where('id', $request->token['user_id'])->first('type');
        if($auth && $auth->type == 'admin') {
            $answers = CompetitionAnswer::where('competition_id', $competition->id)->with([
                'user:id,name,image',
                'competitionAnswerPoint:competition_answer_id,answer_points',
                'competitionAnswerPrize'
            ])->orderBy('degree', 'desc')->orderBy('created_at', 'asc')->get();

            $data['competition'] = $competition;
            $data['answers'] = $answers;
        } else {
            if ($competition->status > 0) {
                $data['competition'] = $competition;
                $userAnswer = CompetitionAnswer::where([
                    'competition_id' => $competition->id,
                    'user_id' => $request->token['user_id']
                ])->first();
                if($userAnswer)
                    $data['userAnswer'] = $userAnswer;
            } else {
                $answers = CompetitionAnswer::where('competition_id', $competition->id)->with([
                    'user:id,name,image',
                    'competitionAnswerPoint:competition_answer_id,answer_points',
                    'competitionAnswerPrize'
                ])->orderBy('degree', 'desc')->orderBy('created_at', 'asc')->get();
                $data['competition'] = $competition;
                $data['answers'] = $answers;
            }
        }
        return $this->mainResponse(true, 'هذه المسايقة التي طلبتها', $data, []);
    }
    // end user actions

    /**
     * @param $answers
     * @param $competition
     * @return void
     */
    private function addPrize($answers, $competition): void
    {
        $i = 0;
        foreach ($answers as $answer) {
            $type = '';
            if ($i == 0)
                $type = 'gold';
            else if ($i == 1)
                $type = 'silver';
            else
                $type= 'bronze';

            CompetitionAnswerPrize::create([
                'competition_id' => $competition->id,
                'competition_answer_id' => $answer->id,
                'type' => $type,
            ]);
            $i++;
        }
    }
}
