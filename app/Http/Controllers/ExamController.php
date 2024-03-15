<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Exams\Answer;
use App\Models\Exams\Exam;
use App\Models\Exams\ExamFinalMark;
use App\Models\Exams\ExamQuestion;
use App\Models\Exams\UserExam;
use App\Models\Exams\UserExamAnswer;
use App\Models\Exams\UserExamResult;
use App\Models\Surveys\UserAnswer;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    use GeneralTrait;
    public function countExams()
    {
        $count = Exam::where('status', 'active')->count();
        return $this->mainResponse(true, 'count', $count);
    }
    public function getExams(Request $request)
    {
        $Categorydata = Category::where('slug', $request->slug)->with([
            'exams:id'
        ])->first(['id', 'name', 'slug']);
        $arr = [];
        foreach ($Categorydata->exams as $key => $value) {
            # code...
            array_push($arr, $value->id);
        }
        $exams = Exam::whereIn('id', $arr)->withCount(
            'examQuestion'
        )->get();
        $user = User::where('type', 'admin')->first(['name', 'image']);
        $test = UserExam::where('user_id', $request->user_id)->get(['exam_id', 'status']);

        return $this->mainResponse(true, '', ['categoryData' => $Categorydata, 'examInfo' => $exams, 'userInfo' => $user, 'userExamsTaken' => $test], []);
    }
    public function checkPoints(Request $request)
    {
        $user = User::where('id', $request->token['user_id'])->first(['id', 'points', 'type']);
        if ($user->type == 'admin' || $user->points >= $request->points) {
            DB::beginTransaction();
            try {
                $userExam = UserExam::where(['user_id' => $request->token['user_id'], 'exam_id' => $request->exam_id])->first();
                if (!$userExam) {
                    if ($user->type == 'user')
                        $user->update([
                            'points' => $user->points - $request->points
                        ]);
                    UserExam::create([
                        'user_id' => $request->token['user_id'],
                        'exam_id' => $request->exam_id,
                    ]);
                    UserExamResult::create([
                        'user_id' => $request->token['user_id'],
                        'exam_id' => $request->exam_id,
                    ]);
                    $updateTaken = Exam::where('id', $request->exam_id)->first();
                    $updateTaken->update([
                        'participants' => ++$updateTaken->participants
                    ]);
                    DB::commit();
                }
                return $this->mainResponse(true, '', [], []);
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->mainResponse(false, 'حدث خطا ما', []);
            }
        }
        return $this->mainResponse(false, 'لا تملك نقاط كافية', [], []);
    }


    public function acceptAnswer(Request $request)
    {
        $total = 0;
        $total1 = 0;
        // $user_answer = UserExam::where('id', $request->exam_id)->first();
        $exam_result = UserExamResult::where(['exam_id' => $request->exam_id, 'user_id' => $request->token['user_id']])->first();
        $user_answer_exam = UserExamAnswer::where(['user_id' => $request->token['user_id'], 'question_id' => $request->question_id])->get();
        $question = ExamQuestion::where('id', $request->question_id)->first();
        DB::beginTransaction();
        try {
            if ($user_answer_exam) {
                foreach ($user_answer_exam as $deleteAnswer) {
                    $ans1 = Answer::where('id', $deleteAnswer->answer_id)->first();
                    if ($ans1->isCorrect == 1) {
                        ++$total1;
                    }
                    $deleteAnswer->delete();
                }
                if ($total1 == $question->count_correct && sizeof($user_answer_exam) == $total1) {
                    $exam_result->update([
                        'results' => --$exam_result->results
                    ]);
                }
            }

            foreach ($request->answers as $answer) {
                UserExamAnswer::create([
                    'answer_id' => $answer,
                    'user_id' => $request->token['user_id'],
                    'question_id' => $request->question_id
                ]);
                $ans = Answer::where('id', $answer)->first();
                if ($ans->isCorrect == 1) {
                    ++$total;
                }
            }
            if ($total == $question->count_correct && sizeof($request->answers) == $total) {
                $exam_result->update([
                    'results' => ++$exam_result->results
                ]);
            }
            db::commit();
            return $this->mainResponse(true, '', [], []);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
            return $this->mainResponse(false, 'أعد إدخال الجواب', [], []);
        }
    }

    public function getQuestions(Request $request)
    {
        $get_question = Exam::where('id', $request->id)->with([
            'examQuestion.answer:id,content,isCorrect,exam_question_id'
        ])->first();
        $questionsId = [];
        $questions = $get_question->examQuestion;
        foreach ($questions as $key => $value) {
            array_push($questionsId, $value->id);
        }
        $userAnswers = UserExamAnswer::where(['user_id' => $request->token['user_id']])
            ->whereIn('question_id', $questionsId)->get(['answer_id', 'question_id']);
        $userExam = UserExam::where(['user_id' => $request->token['user_id'], 'exam_id' => $request->id])->first();
        $duration =  $get_question->duration*60 -  $userExam->created_at->diffInSeconds(now());
        return $this->mainResponse(true, '', ['exam' => $get_question, 'userAnswers' => $userAnswers, 'remainingDuration' => $duration], []);
    }
    public function validateUser(Request $request)
    {
        $userExam = UserExam::where(['user_id' => $request->token['user_id'], 'exam_id' => $request->id])->first();
        if ($userExam && $userExam->status == 'progress') {
            return $this->mainResponse(true, '', []);
        }
        return $this->mainResponse(false, 'لا يمكنك الدخول', []);
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
    public function acceptExamResult(Request $request)
    {
        //db transaction
        $exam = Exam::where('id', $request->exam_id)->withCount([
            'examQuestion'
        ])->first(['id', 'name']);
        $totalResults = UserExamResult::where(['exam_id' => $exam->id, 'user_id' => $request->token['user_id']])->first('results');
        $user_answer = UserExam::where(['user_id' => $request->token['user_id'], 'exam_id' => $request->exam_id])->first();
        $user_answer->update([
            'status' => 'finished'
        ]);

        // calcualte final results mark
        $questionsId = [];
        $questions = $exam->examQuestion;
        foreach ($questions as $key => $value) {
            array_push($questionsId, $value->id);
        }
        $all_answer = UserExamAnswer::where(['user_id' => $request->token['user_id']])
            ->whereIn('question_id', $questionsId)->distinct(['question_id'])->count();
        $unAnswered = $exam->exam_question_count - $all_answer;
        $wrongResults = $exam->exam_question_count - $unAnswered - $totalResults->results;

        $normalResult = $totalResults->results * 20 / $exam->exam_question_count;
        $canadyResult = ($totalResults->results -  $wrongResults) * 20 / $exam->exam_question_count;
        $canady2Result = ($totalResults->results * 2 -  $wrongResults) * 10 / $exam->exam_question_count;

        $normalResult = number_format((float)$normalResult, 2, '.', '');
        $canadyResult = number_format((float)$canadyResult, 2, '.', '');
        $canady2Result = number_format((float)$canady2Result, 2, '.', '');
        ExamFinalMark::create([
            'user_id' => $request->token['user_id'],
            'exam_id' => $request->exam_id,
            'normal_mark' => $normalResult,
            'canady_mark' => $canadyResult,
            'canady2_mark' => $canady2Result
        ]);

        // calcluate points
        $user = User::where('id', $request->token['user_id'])->first(['id', 'points', 'type']);

        if ($user->type == 'user') {
            $admins = $this->getAdmins();
            $usersCountInExam = ExamFinalMark::whereNotIn('user_id', $admins)->count();
            $usersCountFullMark = ExamFinalMark::where('normal_mark', 20)->count();
            $numOfQuestion = $exam->exam_question_count;
            $result = (int)(($totalResults->results / $numOfQuestion) * 20);
            $points = $result;
            if ($usersCountInExam == 1) $points += 100;
            if ($usersCountFullMark == 1) $points += 5000;
            else if ($result == 20) $points += 1000;
            else if ($result >= 18 && $result < 20) $points += 200;
            else if ($result >= 14 && $result < 18) $points += 50;
            else if ($result >= 10 && $result < 14) $points += 10;
            $user->update([
                'points' => $user->points + $points
            ]);
        }
        return $this->mainResponse(true, '', [], []);
    }
    public function getUserResult(Request $request)
    {
        $ans = UserExam::where('status', 'finished')->where(['user_id' => $request->token['user_id'], 'exam_id' => $request->id])->first();
        if ($ans) {
            $user = User::where('id', $ans->user_id)->first(['id', 'name', 'image']);
            $exam = Exam::where('id', $request->id)->withCount([
                'examQuestion'
            ])->first(['id', 'name']);

            $questionsId = [];
            $questions = $exam->examQuestion;
            foreach ($questions as $key => $value) {
                array_push($questionsId, $value->id);
            }
            $all_answer = UserExamAnswer::where(['user_id' => $request->token['user_id']])
                ->whereIn('question_id', $questionsId)->distinct(['question_id'])->count();
            $totalResults = UserExamResult::where(['exam_id' => $exam->id, 'user_id' => $ans->user_id])->first('results');
            return $this->mainResponse(true, '', ['user' => $user, 'examInfo' => $exam, 'allAnswers' => $all_answer, 'totalResults' => $totalResults]);
        }
        return $this->mainResponse(false, 'لا يمكنك الدخول الى هذه الصفحة', []);
    }
    public function getExamUsers(Request $request)
    {
        $users = ExamFinalMark::where('exam_id', $request->exam_id)->with('user:id,name,image')->get();
        if ($users)
            return $this->mainResponse(true, '', $users);
        return $this->mainResponse(false, 'خطا ما', []);
    }
}
