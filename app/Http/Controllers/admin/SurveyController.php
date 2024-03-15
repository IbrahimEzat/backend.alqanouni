<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Survey\SurveyCategory;
use App\Models\Surveys\Question;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    //
    use GeneralTrait;
    public function changeSurveyCategory(Request $request)
    {
        $blogCats = SurveyCategory::where('survey_question_id', $request->survey_id)->delete();

        for ($i = 0; $i < count($request->category_ids); $i++) {
            SurveyCategory::create(['survey_question_id' => $request->survey_id, 'category_id' => $request->category_ids[$i]]);
        }
        return $this->mainResponse(true, 'تم التعديل بنجاح', [], []);
    }

    public function index()
    {
        $surveys = Question::with(['user:id,name'])->orderBy('id', 'desc')->get();
        return $this->mainResponse(true, '', $surveys, []);
    }

    public function getSurveyInfo(Request $request)
    {
        $surveys = Question::where('id', $request->survey_id)->with(['surveyAnswers:question_id,survey_asnwer'])->first();
        return $this->mainResponse(true, '', $surveys, []);
    }

    public function getServeyCategory(Request $request)
    {
        $survey_id = SurveyCategory::where('survey_question_id', $request->survey_id)->get(['category_id']);
        $ids = [];
        foreach ($survey_id as $cat) {
            array_push($ids, $cat->category_id);
        }
        if ($survey_id)
            return $this->mainResponse(true, '', $ids);
        return $this->mainResponse(false, '', []);
    }

    public function changeSurveyStatus(Request $request)
    {
        $change_status = Question::where('id', $request->survey_id)->first();
        $admins = User::where('type', 'admin')->get(['id']);
        $adminIds = [];
        foreach ($admins as $value) {
            array_push($adminIds, $value->id);
        }
        if ($change_status) {
            $change_status->update(['status' => 'active']);
            $countSurveys = Question::where('status', 'active')->whereNotIn('user_id', $adminIds)->count();
            $surveyUser = User::where('id', $change_status->user_id)->first();
            if ($countSurveys === 1) {
                $surveyUser->update([
                    'points' => $surveyUser->points + 1500
                ]);
            }
            $dataNotify = [
                'userNotifyId' => $surveyUser->id,
                'avatar' => '',
                'message' => ' لقد تمت الموافقة على الاستطلاع الخاص بك بعنوان: ' . $change_status->question_name,
                'url' => '/surveys/' . $change_status->id . '/show',
            ];
            return $this->mainResponse(true, 'تم تعديل الحالة بنجاح', $dataNotify, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
    }

    public function deleteSurvey(Request $request)
    {
        $delete_survey = Question::where('id', $request->survey_id)->first();
        if ($delete_survey) {
            $delete_survey->delete();
            $dataNotify = [
                'userNotifyId' => $delete_survey->user_id,
                'avatar' => '',
                'message' => ' قام الأدمن بحذف الاستطلاع الخاصة بك الذي بعنوان: ' . $delete_survey->question_name .
                    ' بسبب ' . $request->reason_delete,
                'url' => '',
            ];
            return $this->mainResponse(true, 'تم حذف الاستطلاع بنجاح', $dataNotify, []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }
}
