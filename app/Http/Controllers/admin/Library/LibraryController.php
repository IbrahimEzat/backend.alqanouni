<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\Library\LibraryCategory;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\Library\Library;

use App\Models\Library\FileProperty;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class LibraryController extends Controller
{
    use GeneralTrait;
    public function getAllLibraries(Request $request)
    {
        $libraries = Library::with([
            'user:id,name',
            'fileProperty:library_id,file_type,file_size,property_rights',
            'aboutLibrary:library_id,about_author,about_file,author_image,file'
        ])->orderBy('id', 'desc')->get();
        return $this->mainResponse(true, 'get all libraries', $libraries);
    }

    public function getCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id'
        ]);
        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);

        }
        $categories = LibraryCategory::where('library_id', $request->library_id)->get(['category_id']);

        $ids = [];
        foreach ($categories as $cat) {
            array_push($ids, $cat->category_id);
        }
        if($categories) {
            return $this->mainResponse(true, 'هذه التصنيفات الخاصة بهذه المكتبة', $ids);
        }

        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
    }

    public function getLibraryInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id'
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);

        }

        $library = Library::where('id', $request->library_id)->with([
            'user:id,name',
            'fileProperty:library_id,file_type,file_size,property_rights',
            'aboutLibrary:library_id,about_author,about_file,author_image,file'
            ])->first();

        if($library) {
            return $this->mainResponse(true, 'تفاصيل المكتبة', $library);
        }
        return $this->mainResponse(false, 'المكتبة غير موجودة', [], [], 422);
    }


//    public function addLibrary(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'user_id' => 'required|exists:users,id',
//            'file_name' => 'required',
//            'num_of_pages' => 'required|numeric',
//            'author_name' => 'required',
//            'release_date' => 'required|date',
//            'file_language' => 'required',
//            'file_cover' => 'required|image',
//            'points' => 'required|numeric',
//            'file_type' => 'required|in:pdf,docx,image,paper',
//            'file_size' => 'required|numeric',
//            'property_rights' => 'required|in:public, author, allowed, not_allowed',
//            'about_author' => 'required',
//            'about_file' => 'required',
//            'author_image' => 'required|image',
//            'file' => Rule::requiredIf(fn () => $request->file_type != 'paper') . '|mimes:png,jpg,docx,pdf',
//        ], [
//            'required' => 'هذا الحقل مطلوب'
//        ]);
//
//        if ($validator->fails()) {
//            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
//        }
//
//
//        DB::beginTransaction();
//        try {
//            $libraryData = [
//                'user_id' => $request->user_id,
//                'file_name' => $request->file_name,
//                'num_of_pages' => $request->num_of_pages,
//                'author_name' => $request->author_name,
//                'release_date' => $request->release_date,
//                'file_language' => $request->file_language,
//                'points' => $request->points,
//                'status' => 'active',
//            ];
//            if ($image = $request->file('file_cover')) {
//                $file_name = time() . "." . $image->getClientOriginalExtension();
//                $image->move(public_path('uploads/library/file-cover'), $file_name);
//                $libraryData['file_cover'] = $file_name;
//            }
//
//            $library = Library::create($libraryData);
//
//            if ($library) {
//                $property = FileProperty::create([
//                    'library_id' => $library->id,
//                    'file_type' => $request->file_type,
//                    'file_size' => $request->file_size,
//                    'property_rights' => $request->property_rights,
//                ]);
//
//                $aboutData = [
//                    'about_author' => $request->about_author,
//                    'about_file' => $request->about_file,
//                    'library_id' => $library->id,
//                ];
//
//                if ($author_image = $request->file('author_image')) {
//                    $image_name = time() . "." . $author_image->getClientOriginalExtension();
//                    $author_image->move(public_path('uploads/library/auhtor_images'), $image_name);
//                    $aboutData['author_image'] = $image_name;
//                }
//
//                //validate file type
//                $imgExt = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'svg', 'webp'];
//                if ($file = $request->file('file')) {
//                    $ex = $file->getClientOriginalExtension();
//                    $check = ($ex == $request->file_type);
//                    if ($request->file_type == 'image') {
//                        $checkImage = in_array($ex, $imgExt);
//                        if ($checkImage) $check = $checkImage;
//                        else return $this->mainResponse(false, 'تأكد من امتداد الصورة', [], ['error' => 'تأكد من امتداد الصورة'], 422);
//                    }
//                    if ($check) {
//                        $file_name = time() . "." . $ex;
//                        $file->move(public_path('uploads/library/files'), $file_name);
//                        $aboutData['file'] = $file_name;
//                    } else {
//                        return $this->mainResponse(false, 'الرجاء تأكد من رفع ملف بنفس الامتداد الذي اخترته في حقل نوع الملف', [], ['error' => 'الرجاء تأكد من رفع ملف بنفس الامتداد الذي اخترته في حقل نوع الملف'], 422);
//                    }
//                }
//                $about = AboutLibrary::create($aboutData);
//
//                // if ($property && $about) {
//                $data = [
//                    'library' => $library,
//                    'property' => $property,
//                    'about' => $about
//                ];
//                DB::commit();
//                return $this->mainResponse(true, 'تم إنشاء المكتبة بنجاح', $data);
//                // }
//            }
//        } catch (\Throwable $th) {
//            DB::rollBack();
//            // throw $th;
//            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
//        }
//    }



    public function updateLibrary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'points' => 'required|numeric',
            'categories' => 'required'
        ], [
            'points.required' => 'حقل النقاط مطلوب',
            'points.numeric' => 'النقاط عبارة عن عدد',
            'categories.required' => 'حقل التصنيفات مطلوب',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $library = Library::where('id', $request->library_id)->first();

        if($library) {
            $library->update([
                'points' => $request->points,
            ]);
            $libraryCategories = LibraryCategory::where('library_id', $request->library_id)->delete();

//            $ca = [2];
            foreach ($request->categories as $cat) {
                LibraryCategory::create([
                    'library_id' => $request->library_id,
                    'category_id' => $cat,
                ]);
            }
            return $this->mainResponse(true, 'تم تعديل المكتبة بنجاح', $library);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);

    }

//    public function updateLibrary(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'library_id' => 'required|exists:libraries,id',
//            'user_id' => 'required|exists:users,id',
//            'categories' => 'required',
//            'file_name' => 'required',
//            'num_of_pages' => 'required|numeric',
//            'author_name' => 'required',
//            'release_date' => 'required|date',
//            'file_language' => 'required',
//            'file_cover' => 'nullable|image',
//            'points' => 'required|numeric',
//            'status' => 'required',
//            'file_type' => 'required|in:pdf,docx,image,paper',
//            'file_size' => 'required|numeric',
//            'property_rights' => 'required|in:public,author,allowed,not_allowed',
//            'about_author' => 'required',
//            'about_file' => 'required',
//            'author_image' => 'nullable|image',
//            'file' => 'nullable|mimes:png,jpg,docx,pdf',
//        ], [
//            'required' => 'هذا الحقل مطلوب'
//        ]);
//
//        if ($validator->fails()) {
//            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
//        }
//
//        DB::beginTransaction();
//        try {
//            $library = Library::where('id', $request->library_id)->first();
//            if ($library) {
//                $libraryData = [
//                    'user_id' => $request->user_id,
//                    'file_name' => $request->file_name,
//                    'num_of_pages' => $request->num_of_pages,
//                    'author_name' => $request->author_name,
//                    'release_date' => $request->release_date,
//                    'file_language' => $request->file_language,
//                    'points' => $request->points,
//                    'status' => $request->status,
//                ];
//                if ($image = $request->file('file_cover')) {
//                    $file_name = time() . "." . $image->getClientOriginalExtension();
//                    $image->move(public_path('uploads/library/file-cover'), $file_name);
//                    $libraryData['file_cover'] = $file_name;
//                }
//
//                $library->update($libraryData);
//                $libCat = LibraryCategory::where('library_id', $request->library_id)->delete();
//
////                //just for api test
////                $c = [1, 2];
//                foreach ($c as $cat) {
//                    LibraryCategory::create([
//                        'library_id' => $request->library_id,
//                        'category_id' => $cat,
//                    ]);
////                }
//                //real production
//                foreach ($request->categories as $cat) {
//                    LibraryCategory::create([
//                        'library_id' => $request->library_id,
//                        'category_id' => $cat,
//                    ]);
//                }
//
//                $property = FileProperty::where('library_id', $request->library_id)->first();
//                if($property) {
//                    $property->update([
//                        'file_type' => $request->file_type,
//                        'file_size' => $request->file_size,
//                        'property_rights' => $request->property_rights,
//                    ]);
//                }
//
//                $about = AboutLibrary::where('library_id', $request->library_id)->first();
//                if($about) {
//                    $aboutData = [
//                        'about_author' => $request->about_author,
//                        'about_file' => $request->about_file,
//                        'library_id' => $library->id,
//                    ];
//
//                    if ($author_image = $request->file('author_image')) {
//                        $image_name = time() . "." . $author_image->getClientOriginalExtension();
//                        $author_image->move(public_path('uploads/library/auhtor_images'), $image_name);
//                        $aboutData['author_image'] = $image_name;
//                    }
//
//                    //validate file type
//                    if ($file = $request->file('file')) {
//                        $imgExt = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'svg', 'webp'];
//                        $ex = $file->getClientOriginalExtension();
//                        $check = ($ex == $request->file_type);
//                        if ($request->file_type == 'image') {
//                            $checkImage = in_array($ex, $imgExt);
//                            if ($checkImage) $check = $checkImage;
//                            else return $this->mainResponse(false, 'تأكد من امتداد الصورة', [], ['error' => 'تأكد من امتداد الصورة'], 422);
//                        }
//                        if ($check) {
//                            $file_name = time() . "." . $ex;
//                            $file->move(public_path('uploads/library/files'), $file_name);
//                            $aboutData['file'] = $file_name;
//                        } else {
//                            return $this->mainResponse(false, 'الرجاء تأكد من رفع ملف بنفس الامتداد الذي اخترته في حقل نوع الملف', [], ['error' => 'الرجاء تأكد من رفع ملف بنفس الامتداد الذي اخترته في حقل نوع الملف'], 422);
//                        }
//                    }
//                    $about->update($aboutData);
//                }
//
//
////            if ($property && $about) {
//                $data = [
//                    'library' => $library,
//                    'property' => $property,
//                    'about' => $about
//                ];
//                DB::commit();
//                return $this->mainResponse(true, 'تم تعديل المكتبة بنجاح', $data);
////            }
//            }
//        } catch (\Throwable $th) {
//            DB::rollBack();
//             throw $th;
//            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
//        }
//
//    }


    private function getAdmins(){
        $admins = User::where('type','admin')->get();
        $adminIds = [];
        foreach ($admins as $value) {
            array_push($adminIds, $value->id);
        }
        return $adminIds;
    }
    public function activeLibrary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occureed', [], $validator->errors()->messages(), 422);
        }

        $library = Library::find($request->library_id);

        if($library) {
            $library->update([
                'status' => 'active'
            ]);
            $adminIds = $this->getAdmins();
            $count = Library::where('status', 'active')->whereNotIn('user_id',$adminIds)->count();
            $user = User::where('id', $library->user_id)->first();
            if($count == 1 && $user->type !== 'admin') {
                $user->update([
                    'points'=> $user->points + 1000,
                ]);
            }
            $property = FileProperty::where('library_id', $library->id)->first();
            if ($property->file_type === 'pdf' || $property->file_type === 'docs') {
                $user->update([
                    'points' => $user->points + 100
                ]);
            } else if ($property->file_type === 'image') {
                $user->update([
                    'points' => $user->points + 10
                ]);
            } else {
                // if($user->points < 1000)
                //     return $this->mainResponse(false, 'المستخدم لا يملك نقاط كافية', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);

                // $user->update([
                //     'points' => $user->points - 1000
                // ]);
            }
            $dataNotify = [
                'userNotifyId' => $library->user_id,
                'avatar'=>'',
                'message' => 'قام الادمن باعتماد الملف الخاص بك و الذي بعنوان '. $library->file_name,
                'url' => '/library/view/'.$library->id
            ];
            return $this->mainResponse(true, 'تم تفعيل المكتبة بنجاح',$dataNotify, []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
    }

    public function disactiveLibrary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
        ]);

        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occureed', [], $validator->errors()->messages(), 422);
        }

        $library = Library::find($request->library_id);

        if($library) {
            $library->update([
                'status' => 'pending'
            ]);
            return $this->mainResponse(true, 'تم إالغاء تفعيل المكتبة بنجاح', []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);

    }

    public function deleteLibrary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $library = Library::find($request->library_id);
        $library->delete();
        $dataNotify = [
            'userNotifyId' => $library->user_id,
            'avatar'=>'',
            'message' => 'قام الادمن بحذف الملف الخاص بك و التي بعنوان '. $library->file_name . ' بسبب '.$request->reason_delete,
            'url' => ''
        ];
        return $this->mainResponse(true, 'تم حذف المكتبة بنجاح',$dataNotify, []);
    }
}
