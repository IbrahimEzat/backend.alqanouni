<?php

namespace App\Http\Controllers;

use App\Models\BlogWishListCount;
use App\Models\WishList;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class BlogWishListCountController extends Controller
{
    use GeneralTrait;

    public function addBlogWishList(Request $request)
    {
        $validator = FacadesValidator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' =>'required|exists:users,id'
        ]);
        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }

        $wishList = WishList::where(['blog_id'=> $request->blog_id , 'user_id'=>$request->user_id])->first();
        if(!$wishList){
            $wishList = WishList::create([
                'blog_id'=>$request->blog_id,
                'user_id'=>$request->user_id,
            ]);
            $wishListCount = BlogWishListCount::where('blog_id',$request->blog_id)->first();
            $wishListCount->update([
                'count'=>$wishListCount->count +1
            ]);
            return $this->mainResponse(true , 'تم اضفافة المقالة الى المقضلة',true ,[] );
        }else{
            $wishList->delete();

            $wishListCount = BlogWishListCount::where('blog_id',$request->blog_id)->first();
            $wishListCount->update([
                'count'=>$wishListCount->count -1
            ]);
            return $this->mainResponse(true , 'تم ازالة المقالة الى المقضلة',false ,[] );
        }
    }

    public function checkWishList(Request $request){
        $validator = FacadesValidator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
            'user_id' =>'required|exists:users,id'
        ]);
        if($validator->fails()) {
            return $this->mainResponse(false, 'validation error occurred', [], $validator->errors()->messages(), 422);
        }
        $wishList = WishList::where(['blog_id'=>$request->blog_id,'user_id'=>$request->user_id])->first();
        if($wishList){
            return $this->mainResponse(true , 'تم اضفافة المقالة الى المقضلة',true ,[] );
        }else{
            return $this->mainResponse(false , 'المقالة مضافة للمفضلة ', false ,[]);
        }
    }
}
