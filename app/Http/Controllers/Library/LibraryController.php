<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Category;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use App\Models\Library\Library;
use App\Models\Library\LibraryView;
use App\Models\Library\AboutLibrary;
use App\Models\Library\FileProperty;
use App\Models\Library\LibraryComment;
use App\Models\Library\LibraryCategory;
use App\Models\Library\LibraryCommentCount;
use App\Models\Library\LibraryWishlist;
use App\Models\Library\LibraryUserPoint;
use App\Models\Library\LibraryPointCount;
use Illuminate\Support\Facades\Validator;
use App\Models\Library\LibraryDownloadCount;
use App\Models\Library\LibraryWishlistCount;
use App\Models\UserFileDownload;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    use GeneralTrait;
    public function index()
    {
        return '';
    }
    public function checkWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = LibraryWishlist::where(['library_id' => $request->library_id, 'user_id' => $request->user_id])->first();

        if ($query) {
            return $this->mainResponse(true, '', true, []);
        }
        return $this->mainResponse(false, '', false, [], 422);
    }
    public function count()
    {
        $count = LibraryCategory::select('library_id')->distinct('library_id')->count();
        return $this->mainResponse(true, 'count', $count, [], []);
    }
    public function getLibrary(Request $request)
    {
        $category = Category::where('slug', $request->slug)->with([
            'libraries:id,file_name,author_name,file_cover,points,user_id,created_at',
            'libraries.user:id,name',
            'libraries.fileProperty:file_type,property_rights,library_id',
            'libraries.libraryCommentCount:comment_count,library_id',
            'libraries.libraryPointCount:point_count,library_id',
            'libraries.libraryDownloadCount:download_count,library_id',
            'libraries.libraryWishListCount:wishlist_count,library_id',
            'libraries.libraryView:views_count,library_id'
        ])->first();

        return $this->mainResponse(true, 'category', $category, [], []);
    }
    public function addLibrary(Request $request)
    {
        $libraryValidate = [
            'file_name' => ['required'],
            'file_cover' => ['required'],
            'num_of_pages' => ['required', 'numeric'],
            'author_name' => ['required'],
            'release_date' => ['required', 'date'],
            'file_language' => ['required'],
            'file_size' => ['required'],
            'property_rights' => ['required'],
            'file_type' => ['required'],
            'about_author' => ['required'],
            'about_file' => ['required'],
            'author_image' => ['required'],
            'file' => ['nullable', 'mimes:doc,docx,pdf,jpg,png,webp,jpeg,svg']
        ];
        $validator = Validator::make($request->all(), $libraryValidate, [
            'required' => 'هذا الحقل مطلوب',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }
        if ($request->file_type !== 'paper' && !$request->file('file'))
            return $this->mainResponse(false, 'validation error occur', [], ['file' =>  ['يجب ارفاق ملف']], 422);
        if ($request->file_type == 'paper' && $request->file('file'))
            return $this->mainResponse(false, 'validation error occur', [], ['file' =>  ['لا يمكن رفع ملف في حالة اختياره من نوع paper']], 422);

        if ($file = $request->file('file')) {
            $imgExt = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'svg', 'webp'];
            $wordExt = ['doc', 'docx'];

            $ex = $file->getClientOriginalExtension();
            if ($request->file_type == 'image') {
                $checkImage = in_array($ex, $imgExt);
                if (!$checkImage)
                    return $this->mainResponse(false, 'تأكد من امتداد الصورة', [], ['file' => ['تأكد من امتداد الصورة']], 422);
            } else if ($request->file_type == 'docs') {
                $checkWord = in_array($ex, $wordExt);
                if (!$checkWord)
                    return $this->mainResponse(false, 'تأكد من امتداد الملف', [], ['file' => ['تأكد من امتداد الملف']], 422);
            } else if ($request->file_type == 'pdf') {
                if ($ex != 'pdf')
                    return $this->mainResponse(false, 'تأكد من امتداد الملف', [], ['file' => ['تأكد من امتداد الملف']], 422);
            }
        }

        $user = User::where('id', json_decode($request->token)->user_id)->first();
        if ($request->file_type === 'paper') {
            if ($user->points < 1000)
                return $this->mainResponse(false, 'تحتاج 1000 نقطة لرفع ملف من نوع معلومات', [], ['file' =>  ['لا يمكن رفع ملف في حالة اختياره من نوع paper']], 422);
        }


        $library = [
            'file_name' => $request->file_name,
            'num_of_pages' => $request->num_of_pages,
            'author_name' => $request->author_name,
            'release_date' => $request->release_date,
            'file_language' => $request->file_language,
            'user_id' => json_decode($request->token)->user_id
        ];
        if ($image = $request->file('file_cover')) {
            $image_name = time() . "." . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/library/file-cover'), $image_name);
            $library['file_cover'] = $image_name;
        }
        DB::beginTransaction();
        try {
            $add_library = Library::create($library);
            if ($add_library) {
                $file_proparety = $this->addFileProperty($request, $add_library->id);
                if ($file_proparety['status'] == false) {
                    $add_library->delete();
                    // return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة الخصائص', [], [], 422);
                    return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة الخصائص', [], $file_proparety['msg'], 422);
                }
                $about_library_file = $this->addAboutLibrary($request, $add_library->id);
                if ($about_library_file['status'] == false) {
                    $add_library->delete();
                    $file_proparety['data']->delete();
                    return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة الخصائص', [],  $about_library_file['msg'], 422);
                }
                if ($user->type !== 'admin' && $request->file_type === 'paper')
                    $user->update(['points' => $user->points - 1000]);
                LibraryCommentCount::create([
                    'library_id' => $add_library->id
                ]);
                LibraryView::create([
                    'library_id' => $add_library->id
                ]);
                LibraryPointCount::create([
                    'library_id' => $add_library->id,
                ]);
                LibraryWishlistCount::create([
                    'library_id' => $add_library->id,
                ]);
                LibraryDownloadCount::create([
                    'library_id' => $add_library->id,
                ]);
                $dataNotify = [
                    'avatar' => $user->image,
                    'message' => 'قام ' . $user->name . ' بإضافة ملف',
                    'url' => '/admin/library/' . $add_library->id
                ];
                DB::commit();
                return $this->mainResponse(true, 'add_library', $dataNotify, []);
            }
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return $this->mainResponse(false, 'حدث خطا فالارسال', [], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }
    public function addFileProperty($request, $library_id)
    {
        $file_type = $request->file_type;
        if (!$request->hasFile('file'))
            $file_type = 'paper';
        $add_file_property = FileProperty::create([
            'library_id' => $library_id,
            'file_type' => $file_type,
            'file_size' => $request->file_size,
            'property_rights' => $request->property_rights
        ]);
        if ($add_file_property) {
            $data = [
                'status' => true,
                'data' => $add_file_property
            ];
            return $data;
        }
        $data = [
            'status' => false,
            'msg' => 'حدث خطأ أثناء عملية الاضافة'
        ];
        return $data;
    }
    public function addAboutLibrary($request, $library_id)
    {

        $about_library = [
            'about_author' => $request->about_author,
            'about_file' => $request->about_file,
            'library_id' => $library_id,
        ];
        if ($auther_image = $request->file('author_image')) {
            $auther_image_name = time() . "." . $auther_image->getClientOriginalExtension();
            $auther_image->move(public_path('uploads/library/auther_images'), $auther_image_name);
            $about_library['author_image'] = $auther_image_name;
        }
        if ($file = $request->file('file')) {
            $file_name = time() . "." . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/library/files'), $file_name);
            $about_library['file'] = $file_name;
        }
        $add_about_library = AboutLibrary::create($about_library);
        if ($add_about_library) {
            $data = [
                'status' => true,
                'data' => $add_about_library
            ];
            return $data;
        }
        $data = [
            'status' => false,
            'msg' => 'حدث خطأ أثناء عملية الاضافة'
        ];
        return $data;
    }
    public function showLibrary(Request $request)
    {
        $library = Library::where('id', $request->library_id)->with([
            'fileProperty:id,file_type,file_size,property_rights,library_id',
            'aboutLibrary:id,about_author,about_file,author_image,file,library_id',
            'libraryPointCount:point_count,library_id',
            'libraryCommentCount:comment_count,library_id',
            'libraryDownloadCount:download_count,library_id',
            'libraryWishListCount:wishlist_count,library_id',
            'libraryView:views_count,library_id',
            'libraryComments.user:id,image,name',
            'user:id,name',

        ])->first();
        return $this->mainResponse(true, 'library', $library, [], []);
    }
    private function getAdmins()
    {
        $admins = User::where('type', 'admin')->get();
        $adminIds = [];
        foreach ($admins as $value) {
            array_push($adminIds, $value->id);
        }
        return $adminIds;
    }
    public function addComment(Request $request)
    {
        $libraryCommentValidate = [
            'comment' => ['required', 'string'],
        ];
        $validator = Validator::make($request->all(), $libraryCommentValidate, [
            'required' => 'هذا الحقل مطلوب',
            'string' => 'الرجاء إدخال نص',
            'exists' => 'يرجى التحقق من البيانات المدخلة',
        ]);
        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occur', [], $validator->errors()->messages(), 422);
        }

        $newComment = LibraryComment::create([
            'comment' => $request->comment,
            'library_id' => $request->library_id,
            'user_id' => $request->token['user_id']
        ]);

        $newComment->load('user');
        if ($newComment) {
            $admins = $this->getAdmins();
            $count = LibraryComment::whereNotIn('user_id', $admins)->count();
            $user = User::where('id', $newComment->user_id)->first();
            if ($count == 1 && $user) {
                $user->update([
                    'points' => $user->points + 500
                ]);
            }
            $userCommentsCount = LibraryComment::where(['user_id' => $request->token['user_id'], 'library_id' => $request->library_id])->count();
            if ($userCommentsCount == 1 && $user)
                $user->update([
                    'points' => $user->points + 3
                ]);

            $q = LibraryCommentCount::where('library_id', $request->library_id)->first();
            $q->update([
                'comment_count' => ++$q->comment_count,
            ]);
            $library = Library::where('id', $request->library_id)->first();
            $dataNotify = [
                'userNotifyId' => $library->user_id,
                'avatar' => $user->image,
                'message' => ' قام ' . $user->name . ' بالتعليق على ملف الخاص بك والذي بعنوان ' . $library->file_name,
                'url' => '/library/view/' . $library->id
            ];
            return $this->mainResponse(true, 'تمت اضافة التعليق بنجاح', ['comment' => $newComment, 'dataNotify' => $dataNotify], []);
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الاضافة', [], [], 422);
    }
    public function deleteComment(Request $request)
    {
        $delete_blogComment = LibraryComment::where('id', $request->comment_id)->first();
        $adminUser = User::where('id', $request->token['user_id'])->first();
        if ($delete_blogComment) {
            if ($delete_blogComment->user_id == $request->token['user_id'] || $adminUser->type == 'admin') {
                $delete_blogComment->delete();
                $q = LibraryCommentCount::where('library_id', $request->library_id)->first();
                $q->update([
                    'comment_count' => --$q->comment_count,
                ]);
                return $this->mainResponse(true, 'تم حذف التعليق بنجاح', [], []);
            } else {
                return $this->mainResponse(false, 'لا يمكنك حذف هذا التعليق', [], [], 403);
            }
        }
        return $this->mainResponse(false, 'حدث خطأ أثناء عملية الحذف', [], [], 422);
    }
    public function increaseView(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        // $library = Library::find($request->library_id);

        // if ($library) {

        $view = LibraryView::where('library_id', $request->library_id)->first();
        if ($view) {
            $viewCount = $view->views_count;
            $view->update([
                'views_count' => ++$viewCount
            ]);
            return $this->mainResponse(true, 'تم زيادة عدد المشاهدات بنجاح', $view);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
    }



    public function increasePoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $userPointRecord = LibraryUserPoint::where(['library_id' => $request->library_id, 'user_id' => $request->user_id])->first();
        $library = Library::where('id', $request->library_id)->first();
        $user = User::where('id', $library->user_id)->first();

        if ($userPointRecord && $userPointRecord->rate == 'add') {
            return $this->mainResponse(false, 'لا يمكنك اضافة المزيد', [], [], 422);
        }

        $query = LibraryPointCount::where('library_id', $request->library_id)->first();

        if ($query) {
            if ($userPointRecord) {
                if ($userPointRecord->rate == 'mid') {
                    $userPointRecord->update([
                        'rate' => 'add'
                    ]);
                    $user->update([
                        'points' => $user->points + 1,
                    ]);
                } else if ($userPointRecord->rate == 'minus') {
                    $userPointRecord->update([
                        'rate' => 'mid'
                    ]);
                }
                $libraryPoints = $query->point_count;
                $query->update([
                    'point_count' => $libraryPoints + 1
                ]);
            } else {
                LibraryUserPoint::create([
                    'library_id' => $request->library_id,
                    'user_id' => $request->user_id,
                    'rate' => 'add'
                ]);
                $libraryPoints = $query->point_count;
                $query->update([
                    'point_count' => ++$libraryPoints
                ]);
                $user->update([
                    'points' => $user->points + 1
                ]);
            }
            return $this->mainResponse(true, 'تم إضافة نقطة إلى الملف', $query->point_count, []);
        }
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
    }

    public function decreasePoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $userPointRecord = LibraryUserPoint::where(['library_id' => $request->library_id, 'user_id' => $request->user_id])->first();
        if ($userPointRecord && $userPointRecord->rate == 'minus') {
            return $this->mainResponse(false, 'لا يمكنك خصم المزيد', [], [], 422);
        }

        $query = LibraryPointCount::where('library_id', $request->library_id)->first();

        if ($userPointRecord) {
            if ($userPointRecord->rate == 'mid') {
                $userPointRecord->update([
                    'rate' => 'minus'
                ]);
            } else if ($userPointRecord->rate == 'add') {
                $userPointRecord->update([
                    'rate' => 'mid'
                ]);
                $user = User::where('id', $request->user_id)->first();
                $user->update([
                    'points' => $user->points - 1,
                ]);
            }

            $libraryPoints = $query->point_count;
            $query->update([
                'point_count' => $libraryPoints - 1
            ]);
        } else {
            LibraryUserPoint::create([
                'library_id' => $request->library_id,
                'user_id' => $request->user_id,
                'rate' => 'minus'
            ]);
            $libraryPoints = $query->point_count;
            $query->update([
                'point_count' => --$libraryPoints
            ]);
        }
        return $this->mainResponse(true, 'تم خصم نقطة من ملف',  $query->point_count, []);
    }

    public function addToWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = LibraryWishlist::where(['library_id' => $request->library_id, 'user_id' => $request->user_id])->first();
        if ($query) {
            $query->delete();
            $libraryWishlist = LibraryWishlistCount::where('library_id', $request->library_id)->first();

            $libraryWishlist->update([
                'wishlist_count' => --$libraryWishlist->wishlist_count,
            ]);
            return $this->mainResponse(true, 'تم ازالة الملف من المفضلة', false, [], 422);
        } else {
            $item = LibraryWishlist::create([
                'library_id' => $request->library_id,
                'user_id' => $request->user_id
            ]);
            if ($item) {
                $libraryWishlist = LibraryWishlistCount::where('library_id', $request->library_id)->first();

                $libraryWishlist->update([
                    'wishlist_count' => ++$libraryWishlist->wishlist_count,
                ]);

                return $this->mainResponse(true, 'تمت إضافة المكتبة إلى مفضلتك', true, []);
            }
            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
        }
    }

    public function deleteFromWishlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $query = LibraryWishlist::where(['library_id' => $request->library_id, 'user_id' => $request->user_id])->first();
        if ($query) {
            $query->delete();
            $count = LibraryWishlistCount::where('library_id', $request->library_id)->first();
            $count->update([
                'wishlist_count' => --$count->wishlist_count
            ]);
            return $this->mainResponse(true, 'تم إزالة المكتبة من مفضلتك', []);
        } else {
            return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
        }
    }

    public function checkDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_id' => 'required|exists:libraries,id',
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->mainResponse(false, 'حدث خطا فالبيانات', [], $validator->errors()->messages(), 422);
        }

        $userDownload = UserFileDownload::where(['user_id' => $request->user_id, 'library_id' => $request->library_id])->first();
        if ($userDownload)
            return $this->mainResponse(true, 'يمكنك تنزيل الملف', []);

        $user = User::find($request->user_id);
        $library = Library::find($request->library_id);

        if ($user->points >= $library->points || $user->type === 'admin') {

            if ($user->type !== 'admin') {
                $adminIds = $this->getAdmins();
                $countDownloadInSite = UserFileDownload::whereNotIn('user_id', $adminIds)->count();

                $points = $user->points;

                if ($countDownloadInSite === 0)
                    $points += 500;
                else
                    $points -= $library->points;
                $user->update([
                    'points' => $points,
                ]);
            }


            UserFileDownload::create([
                'user_id' => $request->user_id,
                'library_id' => $request->library_id
            ]);
            $this->increaseDownload($request);
            return $this->mainResponse(true, 'يمكنك تنزيل الملف', []);
        } else {
            return $this->mainResponse(false, 'نقاطك غير كافية لتنزيل هذا الملف', [], ['error' => 'نقاطك غير كافية لتنزيل هذا الملف'], 422);
        }
    }
    public function increaseDownload(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'library_id' => 'required|exists:libraries,id'
        // ]);

        // if ($validator->fails()) {
        //     return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        // }



        $view = LibraryDownloadCount::where('library_id', $request->library_id)->first();
        if ($view) {
            $downloadCount = $view->download_count;
            $view->update([
                'download_count' => ++$downloadCount,
            ]);
        } else {
            $view = LibraryDownloadCount::create([
                'library_id' => $request->library_id,
                'download_count' => 1,
            ]);
        }
        return $this->mainResponse(true, 'تم زيادة عدد التنزيلات بنجاح', $view);
        return $this->mainResponse(false, 'حدث خطأ ما، حاول مرة أخرى', [], ['error' => 'حدث خطأ ما، حاول مرة أخرى'], 422);
    }

    public function search($search){
        $files= Library::where('file_name','like','%'.$search.'%')->where('status','active')->with([
            'user:id,name',
            'fileProperty:file_type,property_rights,library_id',
            'libraryCommentCount:comment_count,library_id',
            'libraryPointCount:point_count,library_id',
            'libraryDownloadCount:download_count,library_id',
            'libraryWishListCount:wishlist_count,library_id',
            'libraryView:views_count,library_id'
        ])->get();

        return $this->mainResponse(true, '', $files);

    }
}
