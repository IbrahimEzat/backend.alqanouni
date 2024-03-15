<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    use GeneralTrait;

    public function getBolgsInCategiory(Request $request)
    {
        $category_id = Category::where('slug', $request->slug)->first(['id'])->id;
        $category = Category::where('id', $category_id)->with([
            'blogs' => function ($query) {
                // $query->where('blogs.status', 'active');
            },
            'blogs.user:id,name,image',
            'blogs.blogCommentCounts:blog_id,blog_comments',
            'blogs.blogPoints:blog_id,blog_points',
            'blogs.blogWishListCounts:blog_id,count',
            'blogs.blogViews:blog_views,blog_id'
        ])->first();
        // $blogs_in_cat = BlogCategory::where('category_id' ,'=', $category->id)->get(['blog_id']);
        // // $blogs = Blog::whereIn('id',$blog_ids)->with(['user'])->get();
        return $this->mainResponse(true, 'blogCatgiors', $category, []);
    }
}
