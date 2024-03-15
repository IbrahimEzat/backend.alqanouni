<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\StaticPages;
use App\Models\User;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class staticPagesController extends Controller
{
    use GeneralTrait;

    public function addAboutSite(Request $request)
    {
        $Validate = [
            'content' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $page = StaticPages::where('name','aboutSite')->first();
        if(!$page){
            $page = StaticPages::create([
                'name' => 'aboutSite',
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم اضافة صفحة حول الموقع ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
        }else{
            $page->update([
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم تعديل صفحة حول الموقع ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
        }

    }

    public function ShowAboutSite(Request $request){
        $data = StaticPages::where('name','aboutSite')->first();
       return $this->mainResponse(true,'',$data,[]);
    }
    public function addQuestions(Request $request)
    {
        $Validate = [
            'content' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $page = StaticPages::where('name','questions')->first();
        if(!$page){
            $page = StaticPages::create([
                'name' => 'questions',
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم اضافة صفحة حول الموقع ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
        }else{
            $page->update([
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم تعديل صفحة حول الموقع ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
        }

    }

    public function ShowQuestions(Request $request){
        $data = StaticPages::where('name','questions')->first();
       return $this->mainResponse(true,'',$data,[]);
    }

    public function addUsage(Request $request)
    {
        $Validate = [
            'content' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $page = StaticPages::where('name','usage')->first();
        if(!$page){
            $page = StaticPages::create([
                'name' => 'usage',
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم اضافة صفحة  الخصوصية ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
        }else{
            $page->update([
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم تعديل صفحة  الخصوصية ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
        }

    }

    public function ShowUsage(Request $request){
        $data = StaticPages::where('name','usage')->first();
       return $this->mainResponse(true,'',$data,[]);
    }
    public function addCopyrights(Request $request)
    {
        $Validate = [
            'content' => ['required'],
            'token' => ['required'],
        ];
        $validator = Validator::make($request->all(), $Validate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        $page = StaticPages::where('name','copyrights')->first();
        if(!$page){
            $page = StaticPages::create([
                'name' => 'copyrights',
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم اضافة صفحة  الخصوصية ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
        }else{
            $page->update([
                'content' => $request->content,
            ]);
            if ($page) {
                return $this->mainResponse(true, 'تم تعديل صفحة  الخصوصية ', $page, []);
            }
            return $this->mainResponse(false, 'حدث خطأ أثناء عملية التعديل', [], [], 422);
        }

    }

    public function ShowCopyrights(Request $request){
        $data = StaticPages::where('name','copyrights')->first();
       return $this->mainResponse(true,'',$data,[]);
    }

}
