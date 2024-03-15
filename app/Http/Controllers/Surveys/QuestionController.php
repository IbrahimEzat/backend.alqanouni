<?php

namespace App\Http\Controllers\Surveys;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\QuestionUserPoint;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\Surveys\Question;
use App\Models\Surveys\QuestionPoint;
use App\Models\Surveys\QuestionUserVoitCount;
use App\Models\Surveys\QuestionView;
use App\Models\Surveys\QuestionWishlist;
use App\Models\Surveys\QuestionWishlistCount;
use App\Models\Surveys\SurveyAnswer;
use App\Models\Surveys\UserAnswer;
use App\Models\Surveys\UserAnswerStatus;
use App\Models\Surveys\UserSurveyAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    //
    use GeneralTrait;

    public function getSurveyAnswersResult(Request $request)
    {
        $results = UserSurveyAnswer::whereIn('survey_answer_id', $request->survey_answers)->get();
        if ($results)
            return $this->mainResponse(true, 'نتائج التصويت', $results, []);
        return $this->mainResponse(false, 'حدث خطا', [], []);
    }

    public function getCount()
    {
        $count = Question::where('status', 'active')->count();
        return $this->mainResponse(true, 'عدد الاستطلاعات', $count, []);
    }

    public function getQuestion(Request $request)
    {
        $category = Category::where('slug', $request->category_slug)->with([
            'surveys.user:id,name,image',
            'surveys.questionViews:question_views,question_id',
            'surveys.questionWishListCount:question_wishlist_count,question_id',
            'surveys.questionUserVoitCount:number_user_voit,question_id'
        ])->orderBy('id', 'desc')->first();

        $questionAlreadyVoit = null;
        if ($request->token['user_id']) {
            $questionAlreadyVoit = [];
            $answersStatus = UserAnswerStatus::where('user_id', $request->token['user_id'])->get(['question_id']);
            foreach ($answersStatus as $value) {
                array_push($questionAlreadyVoit, $value->question_id);
            }
        }
        return $this->mainResponse(true, 'correct get info', ['questionAlreadyVoit' => $questionAlreadyVoit, 'category' => $category], []);
    }

    public function showQuestion(Request $request)
    {
        $show_question = Question::where('id', $request->survey_id)->with(['user:id,name,job,image,points', 'questionPoints:question_id,question_points', 'questionUserVoitCount:number_user_voit,question_id', 'questionViews:question_views,question_id'])->first();
        $show_answers = SurveyAnswer::where('question_id', $show_question->id)->get(['survey_asnwer', 'id']);
        $userfavioret = QuestionWishlist::where('question_id', $request->survey_id)->where('user_id', $request->token['user_id'])->first();
        $userAnswers = UserAnswer::where(['user_id' => $request->token['user_id'], 'question_id' => $request->survey_id])->get(['survey_answer_id']);
        $userAnswersArrary = [];
        foreach ($userAnswers as $value) {
            # code...
            array_push($userAnswersArrary, $value->survey_answer_id);
        }
        return $this->mainResponse(
            true,
            'Question',
            [
                'question' => $show_question,
                'answers' => $show_answers,
                'userfavioret' => $userfavioret !== null,
                'isAnswerd' => count($userAnswersArrary) !== 0,
                'selectedAnswers' => $userAnswersArrary
            ],
            []
        );
    }

    public function addQuestion(Request $request)
    {
        $question_validate = [
            'question_name' => ['required', 'string'],
            'survey_type' => ['in:one_choice,multi_choices']
        ];
        $validator = Validator::make($request->all(), $question_validate, [
            'required' => 'هذا الحقل مطلوب',
            'survey_type.required' => 'يجب اختيار من داخل المطلوب',
            'string' => 'الرجاء إدخال نص',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        /* calculate discount points*/

        $surveyUser = User::where('id', $request->token['user_id'])->first();
        if($surveyUser) {
            DB::beginTransaction();
            try {
                if ($surveyUser->points >= 500 || $surveyUser->type === 'admin') {
                    $question = Question::create([
                        'question_name' => $request->question_name,
                        'survey_type' => $request->survey_type,
                        'user_id' => $request->token['user_id'],

                    ]);
                    if ($question) {
                        if ($surveyUser->type !== 'admin') {
                            $surveyUser->update([
                                'points' => $surveyUser->points - 500
                            ]);
                        }
                        $this->addSurveyAnswer($request, $question->id);
                        QuestionView::create([
                            'question_id' => $question->id,
                            'question_views' => 0
                        ]);
                        QuestionPoint::create([
                            'question_id' => $question->id,
                            'question_points' => 0
                        ]);
                        QuestionWishlistCount::create([
                            'question_id' => $question->id,
                            'question_wishlist_count' => 0
                        ]);
                        QuestionUserVoitCount::create([
                            'question_id' => $question->id,
                            'number_user_voit' => 0
                        ]);

                        $dataNotify = [
                            'avatar' => '',
                            'message' => 'قام '. $surveyUser->name . ' باضافة استطلاع',
                            'url' => '/admin/surveys/' . $question->id,
                        ];
                        DB::commit();
                        return $this->mainResponse(true, 'تم إنشاء الاستطلاع بنجاح', $dataNotify);
                    }
                }
                return $this->mainResponse(false, 'لا تملك ما يكفي من النقاط . تحتاج على الاقل 500 نقطة لنشره', [], [], 422);

            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], []);
            }

        }

    }

    public function addSurveyAnswer(Request $request, $question_id)
    {
        $survey_answer_validate = [
            'choices' => ['required'],
        ];
        $validator = Validator::make($request->all(), $survey_answer_validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        foreach ($request->choices as $answer) {
            $survey_answer = SurveyAnswer::create([
                'survey_asnwer' => $answer['body'],
                'question_id' => $question_id
            ]);
            if (!$survey_answer) {
                return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], ['error' => 'حدث خطأ أثناء عملية الاضافة'], 422);
            } else {
                $userAanswer = UserSurveyAnswer::create([
                    'survey_answer_id' => $survey_answer->id,
                   'question_id' => $question_id,
                ]);
                if (!$userAanswer) {
                    return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], ['error' => 'حدث خطأ أثناء عملية الاضافة'], 422);
                }
            }
        }
        return $this->mainResponse(true, 'تم اضافة الأسئلة بنجاح', $survey_answer, []);
    }

    public function confirmAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'answers' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], ['error' => 'validation error occurred'], 422);
        }
        $true_status = UserAnswerStatus::where([
            'user_id' => $request->token['user_id'],
            'question_id' => $request->question_id,
        ])->first();
        if ($true_status) {
            return $this->mainResponse(false, 'قمت بالاجابة مسبقا على هذا الاستطلاع', []);
        }
        DB::beginTransaction();
        try {
            $admins = User::where('type', 'admin')->get();
            $adminIds = [];
            foreach ($admins as $value) {
                array_push($adminIds, $value->id);
            }
            $count = UserAnswerStatus::whereNotIn('user_id', $adminIds)->count();
            $user = User::where('id', $request->token['user_id'])->first();

            $pointsAdded = 10;
            // first answer in website
            if ($count === 0 && $user->type !== 'admin') {
                $pointsAdded = 200;
            }
            $user->update([
                'points' => $user->points + $pointsAdded
            ]);

            $user_status = UserAnswerStatus::create([
                'user_id' => $request->token['user_id'],
                'question_id' => $request->question_id
            ]);
            foreach ($request->answers as $answer) {
                UserAnswer::create([
                    'user_id' => $request->token['user_id'], //$request->token['user_id'],
                    'question_id' => $request->question_id,
                    'survey_answer_id' => $answer
                ]);
            }
//            if (!$user_status) {
//                return $this->mainResponse(false, 'حدث خطأ أثناء ارسال البيانات يرجى المحاولة مرة أخرى', [], ['error' => 'حدث خطأ أثناء ارسال البيانات يرجى المحاولة مرة أخرى'], 422);
//            }

            $questionUserVoitCount = QuestionUserVoitCount::where('question_id', $request->question_id)->first();
//            if ($questionUserVoitCount) {
                $questionUserVoitCount->update([
                    'number_user_voit' => ++$questionUserVoitCount->number_user_voit
                ]);
//            }

            // ********
            $gender = User::where('id', $request->token['user_id'],)->first()->gender;
            $query = UserSurveyAnswer::whereIn('survey_answer_id', $request->answers)->get();
            // update answer results in user answers
//            if ($query) {
                if ($gender == 'male') {
                    foreach ($query as $q) {
                        $q->update([
                            'male_gender' => ++$q->male_gender,
                            'answer_result' => ++$q->answer_result,
                        ]);
                    }
                    DB::commit();
                    return $this->mainResponse(true, 'تم إضافة الاجابات بنجاح', []);
                } else {
                    foreach ($query as $q) {
                        $q->update([
                            'female_gender' => ++$q->female_gender,
                            'answer_result' => ++$q->answer_result,
                        ]);
                    }
                    DB::commit();
                    return $this->mainResponse(true, 'تم إضافة الاجابات بنجاح', []);
                }
//            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ أثناء ارسال البيانات يرجى المحاولة مرة أخرى', []);
        }

    }

    public function addViews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $query = QuestionView::where('question_id', $request->question_id)->first();
        if ($query) {
            $query->update([
                'question_views' => ++$query->question_views
            ]);
        } else {
            QuestionView::create([
                'question_id' => $request->question_id,
                'question_views' => 1
            ]);
        }
        return $this->mainResponse(true, 'تم اضافة مشاهدة بنجاح', []);
    }

    public function addWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $item = QuestionWishlist::create([
            'question_id' => $request->question_id,
            'user_id' => $request->token['user_id'],
        ]);

        if ($item) {
            $query = QuestionWishlistCount::where('question_id', $request->question_id)->first();
            if ($query) {
                $query->update([
                    'question_wishlist_count' => ++$query->question_wishlist_count,
                ]);
            } else {
                QuestionWishlistCount::create([
                    'question_id' => $request->question_id,
                    'question_wishlist_count' => 1,
                ]);
            }
            return $this->mainResponse(true, 'تم إضافة الاستطلاع إلى المفضلة', $query);
        }

        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما ، حاول مرة أخرى'], 422);
    }

    public function deleteWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $item = QuestionWishlist::where('question_id', $request->question_id)->first();
        if ($item) {
            $item->delete();
            $query = QuestionWishlistCount::where('question_id', $request->question_id)->first();
            if ($query) {
                $query->update([
                    'question_wishlist_count' => --$query->question_wishlist_count,
                ]);
                return $this->mainResponse(true, 'تم حذف الاستطلاع من المفضلة بنجاح', [], ['error' => 'تم حذف الاستطلاع من المفضلة بنجاح'], 422);
            }
        }
    }

    public function addPoint(Request $request)
    {
        $questionUserPoint = QuestionUserPoint::where('question_id', $request->question_id)->where('user_id', $request->token['user_id'])->first();
        if ($questionUserPoint && $questionUserPoint->rate == 'add')
            return $this->mainResponse(false, 'لا يمكنك اضافة المزيد', [], [], 422);
        $add_point = QuestionPoint::where('question_id', $request->question_id)->first();
        if ($questionUserPoint) {
            if ($questionUserPoint->rate == 'mid') {
                $questionUserPoint->update([
                    'rate' => 'add'
                ]);
            } else if ($questionUserPoint->rate == 'minus') {
                $questionUserPoint->update([
                    'rate' => 'mid'
                ]);
            }
            $add_point->update([
                'question_points' => $add_point->question_points + 1
            ]);
        } else {
            $add_point->update([
                'question_points' => $add_point->question_points + 1
            ]);
            QuestionUserPoint::create([
                'question_id' => $request->question_id,
                'user_id' => $request->token['user_id'],
                'rate' => 'add'
            ]);
        }
        return $this->mainResponse(true, 'تم إضافة التقييم بنجاح', $add_point->question_points, []);
    }

    public function deletePoint(Request $request)
    {
        $questionUserPoint = QuestionUserPoint::where('question_id', $request->question_id)->where('user_id', $request->token['user_id'])->first();
        if ($questionUserPoint && $questionUserPoint->rate == 'minus')
            return $this->mainResponse(false, 'لا يمكنك خصم المزيد', [], [], 422);
        $delete_point = QuestionPoint::where('question_id', $request->question_id)->first();

        if ($questionUserPoint) {
            if ($questionUserPoint->rate == 'mid') {
                $questionUserPoint->update([
                    'rate' => 'minus'
                ]);
            } else if($questionUserPoint->rate == 'add') {
                $questionUserPoint->update([
                    'rate' => 'mid'
                ]);
            }
            $delete_point->update([
                'question_points' => $delete_point->question_points - 1
            ]);
        }
        else {
            QuestionUserPoint::create([
                'question_id' => $request->question_id,
                'user_id' => $request->token['user_id'],
                'rate' => 'minus'
            ]);
            $delete_point->update([
                'question_points' => $delete_point->question_points - 1
            ]);
        }
        return $this->mainResponse(true, 'تم خصم التقييم بنجاح', $delete_point->question_points, []);
    }

     public function checkSurveyActive(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'survey_id' => 'required|exists:questions,id',
         ]);
         if($validator->fails()) {
             return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
         }
         $question = Question::where('id', $request->survey_id)->first('status');
         if($question->status == 'active')
             return $this->mainResponse(true, '', []);
         return $this->mainResponse(false, '', []);
     }

     public function search(Request $request)
     {
         $data = Question::where('question_name', 'like', '%' . $request->keyword . '%')
             ->where('status', 'active')
             ->orderBy('id', 'DESC')
            ->with([
                'user:id,name,image',
                'questionViews:question_views,question_id',
                'questionWishListCount:question_wishlist_count,question_id',
                'questionUserVoitCount:number_user_voit,question_id'
                ])
             ->get();

         $questionAlreadyVoit = null;
         if ($request->token['user_id']) {
             $questionAlreadyVoit = [];
             $answersStatus = UserAnswerStatus::where('user_id', $request->token['user_id'])->get(['question_id']);
             foreach ($answersStatus as $value) {
                 array_push($questionAlreadyVoit, $value->question_id);
             }
         }
         return $this->mainResponse(true, 'correct get info', ['questionAlreadyVoit' => $questionAlreadyVoit, 'data' => $data], []);

     }


}
