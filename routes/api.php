<?php

use App\Events\Customer\BlogCreatedEvent;
use App\Http\Controllers\admin\BannerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\UserBlogController;
use App\Http\Controllers\admin\BlogController;
use App\Http\Controllers\UserCategoryController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\NotificationController;
use App\Http\Controllers\admin\BadgeController;
use App\Http\Controllers\admin\SurveyController;
use App\Http\Controllers\admin\AboutSectionController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogPointController;
use App\Http\Controllers\BlogWishListCountController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\Followings\FollowingController;
use App\Http\Controllers\generalPageController;
use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\NotificationController as userNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Surveys\QuestionController;
use App\Http\Controllers\admin\CompetitionController;
use App\Http\Controllers\Surveys\SurveyAnswerController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\MyAuthMiddleware;
use App\Models\BlogCategory;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\admin\ExamController;
use Pusher\Pusher;
use App\Traits\GeneralTrait;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ExamController as ControllersExamController;
use App\Http\Controllers\admin\staticPagesController;
use App\Http\Controllers\admin\StaffController;
use App\Http\Controllers\ContactController;






/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::prefix('/')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:3');
    Route::post('check-verify-code', [AuthController::class, 'checkVerifyCode']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('email-verify', [AuthController::class, 'emailVerify'])->middleware('throttle:3');
    Route::post('check-email-verify-code', [AuthController::class, 'checkEmailVerifyCode']);

    Route::post('change-info', [AuthController::class, 'updateRegisterinfo']);
    Route::post('change-password', [AuthController::class, 'updatePassword']);
    Route::post('change-about', [AuthController::class, 'updateAboutMe']);
    Route::post('getInfoUser', [AuthController::class, 'getInfo']);
});

Route::post('/about-section', [AboutSectionController::class, 'store'])->middleware([AdminMiddleware::class]);
Route::post('/get-about-section', [AboutSectionController::class, 'getAbout']);
Route::post('/get-about-section-all', [AboutSectionController::class, 'getAboutAll']);

Route::post('/checkUniqueEmail', [AuthController::class, 'checkEmail']);
Route::post('contactForm', [ContactController::class, 'store']);
Route::post('getContacts', [ContactController::class, 'getAllContacts'])->middleware([AdminMiddleware::class]);


Route::post('/getAllMember', [StaffController::class, 'getAllMember']);

Route::prefix('admin/')->group(function () {
    Route::post('/addMember', [StaffController::class, 'addMember']);
    Route::post('/deleteMember', [StaffController::class, 'deleteMember']);
    Route::post('/updateMember', [StaffController::class, 'updateMember']);
});


Route::middleware([AdminMiddleware::class])->prefix('admin/competitions')->group(function () {
    Route::post('/add', [CompetitionController::class, 'add']);
    Route::post('/update', [CompetitionController::class, 'update']);
    Route::post('/list', [CompetitionController::class, 'list']);
    Route::post('/delete', [CompetitionController::class, 'delete']);
    Route::post('/competitionInfo', [CompetitionController::class, 'competitionInfo']);
});

Route::middleware([AdminMiddleware::class])->prefix('admin/staticPages')->group(function () {
    Route::post('/addAboutSite', [staticPagesController::class, 'addAboutSite']);
    Route::post('/addQuestions', [staticPagesController::class, 'addQuestions']);
    Route::post('/addUsage', [staticPagesController::class, 'addUsage']);
    Route::post('/addCopyrights', [staticPagesController::class, 'addCopyrights']);
});

Route::prefix('staticPages')->group(function () {
    Route::post('/ShowAboutSite', [staticPagesController::class, 'ShowAboutSite']);
    Route::post('/ShowQuestions', [staticPagesController::class, 'ShowQuestions']);
    Route::post('/ShowUsage', [staticPagesController::class, 'ShowUsage']);
    Route::post('/ShowCopyrights', [staticPagesController::class, 'ShowCopyrights']);
});

Route::middleware(AdminMiddleware::class)->prefix('admin/exams/')->group(function () {
    Route::post('get', [ExamController::class, 'getExams']);
    Route::post('delete', [ExamController::class, 'deleteExam']);
    Route::post('add', [ExamController::class, 'addExam']);
    Route::prefix('/question')->group(function () {
        Route::post('add', [ExamController::class, 'addQuestion']);
        Route::post('delete', [ExamController::class, 'deleteQuestion']);
        Route::post('get', [ExamController::class, 'getQuestions']);
        Route::post('update', [ExamController::class, 'changeEquestionInfo']);
    });
    Route::post('update', [ExamController::class, 'updateExam']);
    Route::post('accept', [ExamController::class, 'acceptExam']);
    Route::post('examCategory', [ExamController::class, 'examCategory']);
    Route::post('changeExamCategory', [ExamController::class, 'changeExamCategory']);
});

Route::middleware([AdminMiddleware::class])->prefix('admin/badges')->group(function() {
    Route::post('/add', [BadgeController::class, 'add']);
    Route::post('/update', [BadgeController::class, 'update']);
    Route::post('/delete', [BadgeController::class, 'delete']);
    Route::post('/grant', [BadgeController::class, 'grant']);
    Route::post('/getBadges', [BadgeController::class, 'getBadges']);
});

Route::post('/userBadges', [BadgeController::class, 'getUserBadges']);


Route::prefix('exams/')->group(function () {
    Route::post('count', [ControllersExamController::class, 'countExams']);
    Route::post('get', [ControllersExamController::class, 'getExams']);
    Route::post('checkUserPoints', [ControllersExamController::class, 'checkPoints']);
    Route::prefix('/question')->group(function () {
        Route::post('acceptAnswer', [ControllersExamController::class, 'acceptAnswer']);
        Route::post('acceptExam', [ControllersExamController::class, 'acceptExamResult']);
        Route::post('get', [ControllersExamController::class, 'getQuestions']);
        
    });
    Route::post('validateUser', [ControllersExamController::class, 'validateUser']);
    Route::post('getUserResult', [ControllersExamController::class, 'getUserResult']);
    Route::post('getExamUsers', [ControllersExamController::class, 'getExamUsers']);
});

Route::prefix('competitions')->group(function () {
    Route::post('/search', [CompetitionController::class, 'search']);
    Route::post('/count', [CompetitionController::class, 'count']);
    Route::post('/getAllCompetition', [CompetitionController::class, 'getAllCompetition']);
    Route::post('/details', [CompetitionController::class, 'competitionDetails']);
    Route::post('/increaseViews', [CompetitionController::class, 'increaseView']);
    Route::middleware([MyAuthMiddleware::class])->group(function () {
        Route::post('/toggleWishlist', [CompetitionController::class, 'toggleWishlist']);
        Route::post('/checkWishlist', [CompetitionController::class, 'checkWishlist']);
    });
    Route::prefix('/answers')->group(function () {
        Route::middleware([MyAuthMiddleware::class])->group(function () {
            Route::post('/add', [CompetitionController::class, 'addAnswer']);
            Route::post('/update', [CompetitionController::class, 'updateAnswer']);
            Route::post('/increasePoints', [CompetitionController::class, 'increasePoints']);
            Route::post('/decreasePoints', [CompetitionController::class, 'decreasePoints']);
        });
        Route::middleware([AdminMiddleware::class])->group(function () {
            Route::post('/degree/update', [CompetitionController::class, 'updateDegree']);
            Route::post('/submitCorrect', [CompetitionController::class, 'submitCorrect']);
        });
    });
    Route::post('/categories', [CompetitionController::class, 'competitionCategories']);
});

Route::post('/getStatistics', [\App\Http\Controllers\admin\BannerController::class, 'getStatistics'])->middleware([AdminMiddleware::class]);

Route::prefix('profile/')->group(function () {
    Route::post('info', [ProfileController::class, 'getProfileInfo']);
});

Route::middleware([AdminMiddleware::class])->prefix('admin/categories/')->group(function () {
    Route::post('', [CategoryController::class, 'index']);
    Route::post('add', [CategoryController::class, 'storeCategory']);
    Route::post('update', [CategoryController::class, 'updateCategory']);
    Route::post('delete', [CategoryController::class, 'deleteCategory']);
});
Route::prefix('/categories')->group(function () {
    Route::post('', [UserCategoryController::class, 'index']);
    Route::post('withFilesCount', [UserCategoryController::class, 'categoriesWithFilesCount']);
    Route::post('CategotiesWithDiscussions', [UserCategoryController::class, 'CategotiesWithDiscussions']);
    Route::post('/survey', [UserCategoryController::class, 'surveyCategory']);
    Route::post('/blog', [UserCategoryController::class, 'blogCategory']);
    Route::post('/CategotiesWithExams', [UserCategoryController::class, 'CategotiesWithExams']);


});

Route::middleware([AdminMiddleware::class])->prefix('/admin/blogs/')->group(function () {
    Route::post('', [BlogController::class, 'index']);
    Route::post('update', [BlogController::class, 'updateBlog']);
    Route::post('accept', [BlogController::class, 'acceptBlog']);
    Route::post('delete', [BlogController::class, 'deleteBlog']);
    Route::post('getInfo', [BlogController::class, 'getInfo']);
    Route::post('blogCategories', [BlogController::class, 'blogCategories']);
    Route::post('topicblogs', [BlogController::class, 'topicBlogs']);
});

Route::post('topics', [UserCategoryController::class, 'getBlogTopics']);
Route::post('admin/topic/add', [CategoryController::class, 'addTopic'])->middleware([AdminMiddleware::class]);
Route::post('/admin/topics/update', [CategoryController::class, 'updateTopic'])->middleware([AdminMiddleware::class]);
Route::post('/admin/topics/delete', [CategoryController::class, 'deleteTopic'])->middleware([AdminMiddleware::class]);
Route::post('admin/topic/all', [CategoryController::class, 'allTopics']);
Route::post('/topics-details', [CategoryController::class, 'getTopicDetails']);

Route::prefix('/blogs')->group(function () {
    Route::post('', [UserBlogController::class, 'index']);
    Route::post('/add', [UserBlogController::class, 'addBlog'])->middleware(MyAuthMiddleware::class);
    Route::post('/update', [UserBlogController::class, 'updateBlog']);
    Route::post('/show', [UserBlogController::class, 'showBlog']);
    Route::post('/delete', [UserBlogController::class, 'deleteBlog'])->middleware(MyAuthMiddleware::class);
    Route::post('/increase-points', [UserBlogController::class, 'increase']);
    Route::post('/decrease-points', [UserBlogController::class, 'decrease']);
    Route::post('/getBolgsInCategiory', [UserBlogController::class, 'getBolgsInCategiory']);
    Route::post('/increaceView', [UserBlogController::class, 'increaceView']);
    Route::post('/addBlogWishList', [BlogWishListCountController::class, 'addBlogWishList']);
    Route::post('/checkWishList', [BlogWishListCountController::class, 'checkWishList']);
    Route::post('/count', [UserBlogController::class, 'count']);
    Route::post('/search', [UserBlogController::class, 'search']);
    Route::post('/newset', [UserBlogController::class, 'newset']);
    Route::post('/top-blogs', [UserBlogController::class, 'topBlogs']);
    Route::post('/getTopic', [UserBlogController::class, 'getAllBlogTopics']);

});

Route::prefix('/comments')->group(function () {
    Route::post('', [BlogCommentController::class, 'index']);
    Route::post('add', [BlogCommentController::class, 'addComment'])->middleware(MyAuthMiddleware::class);
    Route::post('delete', [BlogCommentController::class, 'deleteComment'])->middleware(MyAuthMiddleware::class);
});




Route::middleware(AdminMiddleware::class)->prefix('/admin/discussions')->group(function () {
    Route::post('/update', [DiscussionController::class, 'updateDiscussion']);
    Route::post('/delete', [DiscussionController::class, 'deleteDiscussion']);
    Route::post('/accept', [DiscussionController::class, 'acceptDiscussion']);
    Route::post('/', [DiscussionController::class, 'index']);
});
Route::prefix('/discussions')->group(function () {
    Route::post('/readyDiscussion', [DiscussionController::class, 'readyDiscussion']);
    Route::post('/add', [DiscussionController::class, 'addDiscussion']);
    Route::post('/count', [DiscussionController::class, 'countDiscussion']);
    Route::post('/addWishlist', [DiscussionController::class, 'addWishlist']);/*done*/
    Route::post('/checkWishList', [DiscussionController::class, 'checkWishList']);
    Route::post('/addview', [DiscussionController::class, 'addviewDiscussion']);
    Route::post('/addPoint', [DiscussionController::class, 'addPointDiscussion']);/*done*/
    Route::post('/minusPoint', [DiscussionController::class, 'minusPointDiscussion']);/*done*/
    Route::post('/getDiscussionsInCategory', [DiscussionController::class, 'getDiscussionsInCategory']);
    Route::post('/show', [DiscussionController::class, 'show']);
    Route::post('/getDiscussion', [DiscussionController::class, 'getDiscussion']);/*done*/

    Route::post('/search', [DiscussionController::class, 'search']);
    Route::post('/getAllOpinionsDiscussion', [DiscussionController::class, 'getAllOpinionsDiscussion']); // done
    Route::post('/addOpinionDiscussion', [DiscussionController::class, 'addOpinionDiscussion']); // done
    Route::post('/deleteOpinion', [DiscussionController::class, 'deleteOpinionDiscussion']);
    Route::post('/setBestOpinion', [DiscussionController::class, 'setBestOpinion']); // done
    Route::post('/checkBestOpinion', [DiscussionController::class, 'checkBestOpinion']); // done
    Route::post('/addPointOpinionDiscussion', [DiscussionController::class, 'addPointOpinionDiscussion']);/*done*/
    Route::post('/muinsePointOpinionDiscussion', [DiscussionController::class, 'muinsePointOpinionDiscussion']);/*done*/

    Route::post('/getAllCommentOpinionDiscussion', [DiscussionController::class, 'getAllCommentOpinionDiscussion']);
    Route::post('/addCommentOpinionDiscussion', [DiscussionController::class, 'addCommentOpinionDiscussion']);/*done*/
    Route::post('/deleteCommentOpinionDiscussion', [DiscussionController::class, 'deleteCommentOpinionDiscussion']);
    Route::post('/addPointCommentOpinion', [DiscussionController::class, 'addPointCommentOpinionDiscussion']);/*done*/
    Route::post('/muinseCommentPointOpinion', [DiscussionController::class, 'muinseCommentPointOpinionDiscussion']);/*done*/

    Route::post('/getCategories', [DiscussionController::class, 'getCategoriesDiscussion']);
});

Route::prefix('chat/')->group(function () {
    Route::post('add', [ChatController::class, 'addMessage']);
    Route::post('/getUsersConnect', [ChatController::class, 'getUsersConnect']);
    Route::post('/getMessagesInChat', [ChatController::class, 'getMessagesInChat']);
    Route::post('/markLastMessageAsRead/{chat_id}', [ChatController::class, 'markLastMessageAsRead']);
});

Route::prefix('notificatoin/')->group(function () {
    Route::post('/unread', [userNotificationController::class, 'getUnread']);
    Route::post('/{offset}/get/{count}', [userNotificationController::class, 'loadMore']);
    Route::post('/getUnreadCount', [userNotificationController::class, 'getUnreadNotificationCount']);
    Route::post('/markAsRead/{id}', [userNotificationController::class, 'markAsRead']);



    Route::prefix('admin/')->group(function () {
        Route::post('new-blog', [NotificationController::class, 'newBlog']);
        Route::post('new-discussion', [NotificationController::class, 'addDiscussion']);
        Route::post('new-file', [NotificationController::class, 'addFile']);
        Route::post('new-survey', [NotificationController::class, 'addSurvey']);
        Route::post('new-subscribe', [NotificationController::class, 'sendNewSubscribeNotify']);
        Route::post('users/givePoints', [NotificationController::class, 'givePoints']);
        Route::post('users/giveBadges', [NotificationController::class, 'giveBadges']);
    });



    Route::post('new-chat-message', [userNotificationController::class, 'newChatMessage']);
    Route::post('new-service-subscribe-message', [userNotificationController::class, 'newServiceSubscribeMessage']);

    Route::post('accept-blog', [userNotificationController::class, 'acceptBlog']);
    Route::post('delete-blog', [userNotificationController::class, 'deleteBlog']);
    Route::post('comment-blog', [userNotificationController::class, 'commentBlog']);
    Route::post('accept-discussion', [userNotificationController::class, 'acceptDiscussion']);
    Route::post('delete-discussion', [userNotificationController::class, 'deleteDiscussion']);
    Route::post('comment-discussion', [userNotificationController::class, 'addOpinDiscussion']);

    Route::post('accept-file', [userNotificationController::class, 'acceptFile']);
    Route::post('delete-file', [userNotificationController::class, 'deleteFile']);
    Route::post('comment-file', [userNotificationController::class, 'addCommentFile']);

    Route::post('accept-survey', [userNotificationController::class, 'acceptSurvey']);
    Route::post('delete-survey', [userNotificationController::class, 'deleteSurvey']);

    Route::post('service-approval', [userNotificationController::class, 'serviceApproval']);
    
    Route::post('competition-correct', [userNotificationController::class, 'competitionCorrect']);
    Route::post('competition-prizes', [userNotificationController::class, 'competitionPrizes']);


});


Route::prefix('banners')->group(function () {
    Route::post('/get', [BannerController::class, 'get']);
});

Route::middleware(AdminMiddleware::class)->prefix('admin/banners')->group(function () {
    Route::post('/add', [BannerController::class, 'add']);
    Route::post('/update', [BannerController::class, 'update']);
    Route::post('/delete', [BannerController::class, 'destroy']);
    Route::post('/getAllBanners', [BannerController::class, 'getAllBanners']);
    Route::post('/getBannerInfo', [BannerController::class, 'getBannerInfo']);
});

Route::prefix('services')->group(function () {
    Route::post('/getServices', [\App\Http\Controllers\ServiceController::class, 'getServices']);
    Route::post('/getServiceInfo', [\App\Http\Controllers\ServiceController::class, 'getServiceInfo']);
    Route::post('/getServiceReview', [\App\Http\Controllers\ServiceController::class, 'getReview']);
    Route::post('/checkServicePoints', [\App\Http\Controllers\ServiceController::class, 'checkPoints']);
    //        Route::post('/confirmSubscribe', [\App\Http\Controllers\ServiceController::class, 'confirmSubscribe']);
    // });
    Route::post('/addMessageToSubscribe', [\App\Http\Controllers\ServiceController::class, 'addMessageToSubscribe']);
    Route::post('/getMessagesInSubscribe', [\App\Http\Controllers\ServiceController::class, 'getMessagesInSubscribe']);
    Route::middleware(MyAuthMiddleware::class)->group(function () {
        Route::post('/getUserServices', [\App\Http\Controllers\ServiceController::class, 'getUserServices']);
        Route::post('/confirmSubscribe', [\App\Http\Controllers\ServiceController::class, 'confirmSubscribe']);
        Route::post('/checkSubscription', [\App\Http\Controllers\ServiceController::class, 'checkSubscription']);
        Route::post('/submitService', [\App\Http\Controllers\ServiceController::class, 'submitService']);
        Route::post('/checkSubmit', [\App\Http\Controllers\ServiceController::class, 'checkSubmit']);
        Route::post('/submitReview', [\App\Http\Controllers\ServiceController::class, 'submitReview']);
    });
});
Route::post('checkToken', function (Request $request) {
    $personalToken = PersonalAccessToken::where('id', $request->token['id'])->first();
    if (!$personalToken)
        return response()->json([
            'status' => false,
        ]);
    if ($personalToken->user_id != $request->token['user_id'])
        return response()->json([
            'status' => false,
        ]);
    if (Hash::check($request->token['returnedToken'], $personalToken->token)) {
        return response()->json([
            'status' => true,
        ]);
    }

    return response()->json([
        'status' => false,
    ]);
});
Route::middleware(AdminMiddleware::class)->prefix('admin/services')->group(function () {
    Route::post('/getAllSubscriptions', [\App\Http\Controllers\admin\ServiceController::class, 'getAllSubscriptions']);

    Route::post('/add', [\App\Http\Controllers\admin\ServiceController::class, 'add']);
    Route::post('/update', [\App\Http\Controllers\admin\ServiceController::class, 'update']);
    Route::post('/delete', [\App\Http\Controllers\admin\ServiceController::class, 'deleteService']);
    Route::post('/getServices', [\App\Http\Controllers\admin\ServiceController::class, 'getServices']);
    Route::post('/getServiceInfo', [\App\Http\Controllers\admin\ServiceController::class, 'getServiceInfo']);
});







Route::post('/getUserFollowInfo', [FollowingController::class, 'getUserFollowInfo']);

Route::middleware(MyAuthMiddleware::class)->prefix('/')->group(function () {
Route::post('follow/add', [FollowingController::class, 'addFollow']);
Route::post('follow/delete', [FollowingController::class, 'deleteFollow']);
});


Route::prefix('admin/surveys')->group(function () {
    Route::post('', [SurveyController::class, 'index']);
    Route::post('/getSurveyInfo', [SurveyController::class, 'getSurveyInfo']);
    Route::post('/getServeyCategory', [SurveyController::class, 'getServeyCategory']);
    Route::post('/changeSurveyCategory', [SurveyController::class, 'changeSurveyCategory'])->middleware(AdminMiddleware::class);
    Route::post('/changeSurveyStatus', [SurveyController::class, 'changeSurveyStatus'])->middleware(AdminMiddleware::class);
    Route::post('/delete', [SurveyController::class, 'deleteSurvey'])->middleware(AdminMiddleware::class);
});

Route::prefix('surveys/')->group(function () {
    Route::post('', [QuestionController::class, 'index']);
    Route::post('getSurveyInfo', [QuestionController::class, 'getSurveyInfo']);
    Route::post('index', [QuestionController::class, 'getQuestion']);
    Route::post('show', [QuestionController::class, 'showQuestion']);
    Route::post('getCount', [QuestionController::class, 'getCount']);
    Route::post('getResults', [QuestionController::class, 'getSurveyAnswersResult']);
    Route::post('checkSurveyActive', [QuestionController::class, 'checkSurveyActive']);
    Route::post('searchInSurveys', [QuestionController::class, 'search']);
    // views
    Route::post('views/add', [QuestionController::class, 'addViews']);

    Route::middleware(MyAuthMiddleware::class)->group(function () {
        Route::post('add', [QuestionController::class, 'addQuestion']);
        Route::post('confirm', [QuestionController::class, 'confirmAnswer']);
        Route::post('answers/add', [SurveyAnswerController::class, 'addSurveyAnswer']);
        // wishlist
        Route::post('wishlist/add', [QuestionController::class, 'addWishlist']);
        Route::post('wishlist/delete', [QuestionController::class, 'deleteWishlist']);
        // point
        Route::post('points/add', [QuestionController::class, 'addPoint']);
        Route::post('points/delete', [QuestionController::class, 'deletePoint']);
    });
});




Route::prefix('library/')->group(function () {
    Route::post('/search/{search}', [LibraryController::class, 'search']);
    Route::post('get', [LibraryController::class, 'getLibrary']);
    Route::post('add', [LibraryController::class, 'addLibrary']);
    Route::post('show', [LibraryController::class, 'showLibrary']);
    Route::post('comment/add', [LibraryController::class, 'addComment']);
    Route::post('comment/delete', [LibraryController::class, 'deleteComment']);
    Route::post('count', [LibraryController::class, 'count']);
    Route::post('/checkWishlist', [LibraryController::class, 'checkWishlist']);
    Route::post('increaseView', [LibraryController::class, 'increaseView']);
    Route::post('increaseDownload', [LibraryController::class, 'increaseDownload']);
    Route::post('checkDownload', [LibraryController::class, 'checkDownload']);
    Route::post('increasePoint', [LibraryController::class, 'increasePoint']);
    Route::post('decreasePoint', [LibraryController::class, 'decreasePoint']);
    Route::post('addToWishlist', [LibraryController::class, 'addToWishlist']);
    Route::post('deleteFromWishlist', [LibraryController::class, 'deleteFromWishlist']);
});


Route::middleware(AdminMiddleware::class)->prefix('admin/library')->group(function () {
    Route::post('/getLibraries', [\App\Http\Controllers\admin\Library\LibraryController::class, 'getAllLibraries']); //done
    //    Route::post('/add', [\App\Http\Controllers\Admin\Library\LibraryController::class, 'addLibrary']); //done
    Route::post('/update', [\App\Http\Controllers\admin\Library\LibraryController::class, 'updateLibrary']); // done
    Route::post('/delete', [\App\Http\Controllers\admin\Library\LibraryController::class, 'deleteLibrary']);
    Route::post('/activateLibrary', [\App\Http\Controllers\admin\Library\LibraryController::class, 'activeLibrary']); //done
    Route::post('/deactivateLibrary', [\App\Http\Controllers\admin\Library\LibraryController::class, 'disactiveLibrary']); //done
    Route::post('/getCategories', [\App\Http\Controllers\admin\Library\LibraryController::class, 'getCategories']);
    Route::post('/getLibraryInfo', [\App\Http\Controllers\admin\Library\LibraryController::class, 'getLibraryInfo']);
});

Route::post('/arrangementUsers', [generalPageController::class, 'arrangementUsers']);




Route::post('/pusher/auth', function (Request $request) {

    $pusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ]);

    $socket_id = $request->input('socket_id');
    $channel_name = $request->input('channel_name');

    $auth = $pusher->authenticateUser($channel_name, $socket_id, [
        'user_id' => 1,
        'user_info' => [
            'name' => 'khaled',
        ],
    ]);

    return response($auth);
});
// Route::post('send-notification', function (Request $request) {

//     $admin = \App\Models\User::where('type', 'admin')->first();
// //    return \App\Models\User::find(2)->unreadNotifications->count();
//     // $blog = \App\Models\Blog::create([
//     //     'title' => 'Blog18 title Blog18 title 5',
//     //     'content' => 'Blog18 content Blog18 content Blog18 content Blog18 content Blog18 content',
//     //     'slug' => \Illuminate\Support\Str::slug('Blog18 title Blog18 title 5'),
//     //     'user_id' => 2
//     // ]);
//     $blog= [];
//     broadcast(new BlogCreatedEvent($blog));
    // $admin->notify(new \App\Notifications\Admin\BlogCreatedNotification($blog));
// });

    // Route::post('/add', [UserCategoryContrller::class, 'storeCategory']);
    // Route::post('/update', [UserCategoryContrller::class, 'updateCategory']);
    // Route::post('/delete', [UserCategoryContrller::class, 'deleteCategory']);
    // Route::post('/add', [BlogController::class, 'addBlog']);
