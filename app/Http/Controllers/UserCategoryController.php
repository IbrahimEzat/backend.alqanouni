<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Category;
use App\Traits\GeneralTrait;


class UserCategoryController extends Controller
{
    //
    use GeneralTrait;
    public function index()
    {
        $categories = Category::all();
        return $this->mainResponse(true, 'categories', $categories, []);
    }
    public function categoriesWithFilesCount()
    {
        $categories = Category::withCount('libraries')->get();
        return $this->mainResponse(true, 'categories', $categories, []);
    }
    public function CategotiesWithDiscussions()
    {
        $categories = Category::withCount('discussions')->get();
        return $this->mainResponse(true, 'categories', $categories, []);
    }

    public function surveyCategory()
    {
        $surveys = Category::withCount('surveys')->get();
        return $this->mainResponse(true, 'surveys', $surveys, []);
    }
    public function blogCategory()
    {
        $categories = Category::withCount('blogs')->get();
        return $this->mainResponse(true, 'categories', $categories, []);
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
        $user = User::where('type', 'admin')->get(['name', 'image']);
        $test = UserExam::where('user_id', $request->user_id)->get('exam_id');
        $arr1 = [];
        foreach ($test as $value) {
            array_push($arr1, $value->exam_id);
        }

        return $this->mainResponse(true, '', ['categoryData' => $Categorydata, 'examInfo' => $exams, 'userInfo' => $user, 'userExamsTaken' => $arr1], []);
    }
    public function CategotiesWithExams(){
        $categories = Category::withCount('exams')->get();
        return $this->mainResponse(true, 'categories', $categories, []);
    }
    public function getBlogTopics()
    {
        $topics = Topic::where('type', 'blog')->get();
        return $this->mainResponse(true, 'topics', $topics, []);
    }
}
