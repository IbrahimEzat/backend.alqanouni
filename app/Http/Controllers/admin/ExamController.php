<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Exams\Answer;
use App\Models\Exams\Exam;
use App\Models\Exams\ExamCategory;
use App\Models\Exams\ExamQuestion;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExamController extends Controller
{
    use GeneralTrait;
    public function getExams()
    {
        $exam = Exam::all();
        return $this->mainResponse(true, '', $exam, []);
    }
    public function deleteExam(Request $request)
    {
        $delete_exam = Exam::where('id', $request->id)->first();
        $delete_exam->delete();
        if ($delete_exam) {
            return $this->mainResponse(true, 'success delete', [], []);
        }
        return $this->mainResponse(false, 'حصل خطأ أثناء عملية الحذف', [], ['error' => 'حصل خطأ أثناء عملية الحذف']);
    }
    public function addExam(Request $request)
    {
        $exam_validate = [
            'name' => ['required']
        ];
        $validate = Validator::make($request->all(), $exam_validate, [
            'required' => 'هذا الحقل مطلوب'
        ]);
        if ($validate->fails()) {
            return $this->mainResponse(false, 'حصل خطأ ما', [], ['error' => $validate->errors()->messages()]);
        }
        $add_exam = Exam::create([
            'name' => $request->name,
            'duration' => $request->duration,
            'points' => $request->points
        ]);
        $add_exam->refresh();
        if ($add_exam) {
            return $this->mainResponse(true, 'تم إضافة اختبار بنجاح', $add_exam);
        }
        return $this->mainResponse(false, 'حصل خطأ ما أثناء عملية الإضافة', [], ['error' => 'حصل خطأ ما أثناء عملية الإضافة']);
    }
    public function addQuestion(Request $request)
    {
        $question_validate = [
            'content' => ['required'],
        ];
        $validate = Validator::make($request->all(), $question_validate, [
            'required' => 'هذا الحقل مطلوب'
        ]);
        if ($validate->fails()) {
            return $this->mainResponse(false, 'حصل خطأ ما', [], ['error' => $validate->errors()->messages()]);
        }
        DB::beginTransaction();
        try {

            $result = 0;
            $add_exam = ExamQuestion::create([
                'content' => $request->content,
                'answer_type' => $request->answer_type,
                'exam_id' => $request->exam_id,
                'count_correct' => 0
            ]);
            foreach ($request->answers as $answer) {
                Answer::create([
                    'content' => $answer['content'],
                    'isCorrect' => $answer['isCorrect'],
                    'exam_question_id' => $add_exam['id']
                ]);
                if ($answer['isCorrect'] == 1) {
                    ++$result;
                }
            }

            $add_exam->update([
                'count_correct' => $result
            ]);

            $add_exam->load('answer:id,content,isCorrect,exam_question_id');
            DB::commit();
            return $this->mainResponse(true, 'تم إنشاء سؤال بنجاح', $add_exam);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], []);
        }
    }
    public function deleteQuestion(Request $request)
    {
        $delete_question = ExamQuestion::where('id', $request->id)->first();
        if (!$delete_question) {
            return $this->mainResponse(false, 'لا يوجد سؤال للحذف', [], []);
        }
        $delete_question->delete();
        if ($delete_question) {
            return $this->mainResponse(true, 'تم الحذف بنجاح', [], []);
        }
        return $this->mainResponse(false, 'حصل خطأ أثناء عملية الحذف', [], ['error' => 'حصل خطأ أثناء عملية الحذف']);
    }
    public function getQuestions(Request $request)
    {
        $get_question = Exam::where('id', $request->id)->with([
            'examQuestion.answer:id,content,isCorrect,exam_question_id'
        ])->first();
        return $this->mainResponse(true, '', $get_question, []);
    }

    public function updateExam(Request $request)
    {
        $update_exam = Exam::where('id', $request->id)->first();
        if ($update_exam) {
            $correct = $update_exam->update([
                'duration' => $request->duration,
                'points' => $request->points,
                'name' => $request->examName
            ]);
            if ($correct) {
                return $this->mainResponse(true, 'تم التعديل بنجاح', []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], ['error' => 'حدث خطأ أثناء عملية التعديل']);
        }
        return $this->mainResponse(false, 'حصل خطأ ما', [], ['error' => 'حصل خطأ ما']);
    }
    public function acceptExam(Request $request)
    {
        $accept_exam = Exam::where('id', $request->id)->first();
        if ($accept_exam) {
            $accept = $accept_exam->update([
                'status' => 'active'
            ]);
            if ($accept) {
                return $this->mainResponse(true, 'تم اعتماد الاختبار', []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], ['error' => 'حدث خطأ أثناء عملية التعديل']);
        }
        return $this->mainResponse(false, 'حصل خطأ ما', [], ['error' => 'حصل خطأ ما']);
    }

    public function examCategory(Request $request)
    {
        $categories_id = ExamCategory::where('exam_id', $request->exam_id)->get(['category_id']);
        $ids = [];
        foreach ($categories_id as $cat) {
            array_push($ids, $cat->category_id);
        }
        if ($categories_id)
            return $this->mainResponse(true, '', $ids);
        return $this->mainResponse(false, '', []);
    }

    public function changeExamCategory(Request $request)
    {
        $deleteCategory = ExamCategory::where('exam_id', $request->exam_id)->get();
        for ($i = 0; $i < count($deleteCategory); $i++) {
            $deleteCategory[$i]->delete();
        }

        for ($i = 0; $i < count($request->category_ids); $i++) {
            ExamCategory::create(['exam_id' => $request->exam_id, 'category_id' => $request->category_ids[$i]]);
        }
        if ($deleteCategory)
            return $this->mainResponse(true, 'تم التعديل بنجاح', []);
        return $this->mainResponse(false, 'يوجد خطا ما', []);
    }

    public function changeEquestionInfo(Request $request){
        $question_validate = [
            'content' => ['required'],
        ];
        $validate = Validator::make($request->all(), $question_validate, [
            'required' => 'هذا الحقل مطلوب'
        ]);
        if ($validate->fails()) {
            return $this->mainResponse(false, 'حصل خطأ ما', [], ['error' => $validate->errors()->messages()]);
        }
        DB::beginTransaction();
        try {
            $examQuestion = ExamQuestion::where('id',$request->questionId)->first();
            $answers = Answer::where('exam_question_id',$request->questionId)->get();
            $index = 0;
            $result = 0;
            foreach ($answers as $key => $answer) {
                $answer->update([
                    'content' => $request->answers[$index]['content'] ,
                     'isCorrect' =>$request->answers[$index]['isCorrect'],
                ]);
                if ($request->answers[$index]['isCorrect'] == 1) {
                    ++$result;
                }
                $index++;
            }
            $examQuestion->update([
                'content' => $request->content,
                'answer_type' => $request->answer_type,
                'count_correct' => $result
            ]);

            DB::commit();
            return $this->mainResponse(true, 'تم تعديل السؤال بنجاح', []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], []);
        }
    }
}
