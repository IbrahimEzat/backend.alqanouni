<?php

namespace App\Models\Library;

use App\Models\User;
use App\Models\Category;
use App\Models\Library\LibraryView;
use App\Models\Library\AboutLibrary;
use App\Models\Library\FileProperty;
use App\Models\Library\LibraryComment;
use App\Models\Library\LibraryWishlist;
use Illuminate\Database\Eloquent\Model;
use App\Models\Library\LibraryUserPoint;
use App\Models\Library\LibraryPointCount;
use App\Models\Library\LibraryCommentCount;
use App\Models\Library\LibraryDownloadCount;
use App\Models\Library\LibraryWishlistCount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Library extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function fileProperty()
    {
        return $this->hasOne(FileProperty::class);
    }
    public function aboutLibrary()
    {
        return $this->hasOne(AboutLibrary::class);
    }
    public function libraryComments()
    {
        return $this->hasMany(LibraryComment::class);
    }
    public function libraryCommentCount()
    {
        return $this->hasOne(LibraryCommentCount::class);
    }
    public function libraryDownloadCount()
    {
        return $this->hasOne(LibraryDownloadCount::class);
    }
    public function libraryUserPoints()
    {
        return $this->hasMany(LibraryUserPoint::class);
    }
    public function libraryPointCount()
    {
        return $this->hasOne(LibraryPointCount::class);
    }
    public function libraryWishLists()
    {
        return $this->hasMany(LibraryWishlist::class);
    }
    public function libraryWishListCount()
    {
        return $this->hasOne(LibraryWishlistCount::class);
    }
    public function libraryView()
    {
        return $this->hasOne(LibraryView::class);
    }
    public function category()
    {
        return $this->belongsToMany(Category::class);
    }
    public function getFileCoverAttribute()
    {
        return asset('public/uploads/library/file-cover/' . $this->attributes['file_cover']);
    }

}
