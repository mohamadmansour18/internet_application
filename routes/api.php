<?php

use App\Http\Controllers\Agency_Domain\AgencyController;
use App\Http\Controllers\Audit\NotificationController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Complaints_Domain\ComplaintController;
use App\Http\Controllers\Complaints_Domain\ComplaintTypeController;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('/v1/citizen')->group(function () {

    Route::post('/register' , [UserController::class , 'registerCitizen'])->middleware(['throttle:registerApi' , 'Logging:register.citizen']);
    Route::post('/login' , [UserController::class , 'loginCitizen'])->middleware(['throttle:loginApi' , 'Logging:login.citizen']);

    Route::post('/verifyAccount' , [UserController::class , 'verifyRegistrationCitizen'])->middleware(['Logging:verify.citizen.account']);
    Route::post('/resendOtp' , [UserController::class , 'resendOtp'])->middleware(['throttle:registerApi' , 'Logging:resendOtp.for.verification']);

    /** @noinspection PhpParamsInspection */
    Route::middleware(['throttle:forgotPasswordApi' , 'Logging:reset.password'])->group(function () {

        Route::post('/forgotPassword' , [UserController::class , 'forgotPassword']);
        Route::post('/verifyForgotPasswordEmail' , [UserController::class , 'verifyForgotPasswordEmail']);
        Route::post('/resetPassword' , [UserController::class , 'resetPassword']);
        Route::post('/resendPasswordResetOtp' , [UserController::class , 'resendPasswordResetOtp']);

    });

    /** @noinspection PhpParamsInspection */
    Route::middleware(['jwt' ,'role:citizen' , 'throttle:roleBasedApi'])->group(function () {
        Route::get('/logout' , [UserController::class , 'logout'])->middleware('Logging:logout');

        Route::prefix('/home')->group(function () {

            Route::post('/showComplaints' , [ComplaintController::class , 'getCitizenComplaints'])->middleware('Logging:show.citizen.complaints');
            Route::post('/searchComplaint' , [ComplaintController::class , 'SearchComplaint'])->middleware('Logging:search.citizen.complaints');

            Route::post('/createComplain' , [ComplaintController::class , 'createCitizenComplaint'])->middleware('Logging:create.complaint');
            Route::get('/agencyByName' , [AgencyController::class , 'agencies']);
            Route::get('/ComplaintTypeByName' , [ComplaintTypeController::class , 'complaintTypes']);
        });

        Route::prefix('/complaint')->group(function () {

            Route::get('/getDetails/{complain_id}' , [ComplaintController::class , 'getCitizenComplaintDetails'])->middleware('Logging:show.citizen.complaint.details');
            Route::delete('/delete/{complain_id}' , [ComplaintController::class , 'deleteComplaint'])->middleware('Logging:delete.complaint');
            Route::post('/addDetails/{complain_id}' , [ComplaintController::class , 'addExtraInfoToComplaint'])->middleware('Logging:add.extra.complaint.details');
        });

        Route::get('/notification' , [NotificationController::class , 'getCitizenNotifications'])->middleware('Logging:show.citizen.notification');
    });
});

Route::prefix('/v1/both')->group(function () {

    Route::post('/login' , [UserController::class , 'loginForDashboard'])->middleware(['throttle:loginApi' , 'Logging:login.for.dashboard']);

    /** @noinspection PhpParamsInspection */
    Route::middleware(['throttle:forgotPasswordApi' , 'Logging:reset.password.for.dashboard'])->group(function () {

        Route::post('/forgotPassword' , [UserController::class , 'forgotPassword']);
        Route::post('/verifyForgotPasswordEmail' , [UserController::class , 'verifyForgotPasswordEmail']);
        Route::post('/resetPassword' , [UserController::class , 'resetPassword']);
        Route::post('/resendPasswordResetOtp' , [UserController::class , 'resendPasswordResetOtp']);

    });

    /** @noinspection PhpParamsInspection */
    Route::middleware(['jwt' ,'role:both' , 'throttle:roleBasedApi'])->group(function () {

        Route::get('/logout' , [UserController::class , 'logout'])->middleware('Logging:dashboard.logout');

        Route::prefix('/ComplaintManagement')->group(function () {
            Route::post('/getComplaint' , [ComplaintController::class , 'getComplaintBasedRole'])->middleware('Logging:show.complaint.based.role');
            Route::get('/getDetails/{complain_id}' , [ComplaintController::class , 'ComplaintDetails'])->middleware('Logging:show.complaint.details.dashboard');

            Route::post('/inProgress/{complain_id}' , [ComplaintController::class , 'StartProcessingComplaint'])->middleware('Logging:start.complaint.processing');
            Route::post('/reject/{complain_id}' , [ComplaintController::class , 'rejectComplaint'])->middleware('Logging:reject.complaint');
            Route::post('/finish/{complain_id}' , [ComplaintController::class , 'finishProcessingComplaint'])->middleware('Logging:finish.complaint.processing');
            Route::post('/moreInfo/{complain_id}' , [ComplaintController::class , 'requestMoreInfoToComplaint'])->middleware('Logging:add.extra.complaint.details');
        });

    });
});

Route::prefix('/v1/manager')->group(function () {
    /** @noinspection PhpParamsInspection */
    Route::middleware(['jwt' ,'role:manager' , 'throttle:roleBasedApi'])->group(function () {

        Route::prefix('/home')->group(function () {
            Route::post('/getComplaintStatsMonth' , [ComplaintController::class , 'statsByMonth'])->middleware('Logging:get.complaint.stats.month');
            Route::get('/getComplaintStatsYear' , [ComplaintController::class , 'yearlySummary'])->middleware('Logging:get.complaint.stats.year');
            Route::post('/downloadStats' , [ComplaintController::class , 'downloadYearlyStats'])->middleware('Logging:download.stats');
        });

        Route::prefix('/UserManagement')->group(function () {
            Route::post('/getCitizen' , [UserController::class , 'paginateCitizen'])->middleware('Logging:get.citizen');
            Route::post('/getOfficer' , [UserController::class , 'paginateOfficer'])->middleware('Logging:get.officer');
            Route::get('/deactivate/{user_id}' , [UserController::class , 'deactivate'])->middleware('Logging:deactivate.user');
            Route::get('/putActive/{user_id}' , [UserController::class , 'activate'])->middleware('Logging:activate.user');
            Route::post('/createOfficer' , [UserController::class , 'createOfficer'])->middleware('Logging:create.officer');
            Route::get('/agencyByName' , [AgencyController::class , 'agencies']);
        });

    });
});
Route::post('/refresh' , [UserController::class , 'refresh'])->middleware('jwt.refresh');


Route::get('/test-fcm', function (FirebaseNotificationService $fcm) {
    \App\Events\FcmNotificationRequested::dispatch([3] , "الوووووو" , "مرحبا زعييييم");
    return "تمت العملية بنجاح";
//    try {
//        $fcm->send(
//            'Hello Obeda',
//            'Test',
//            ['feQ0xpsbSAS89BkAniZ-F8:APA91bFuSmS4SLeYZvzsOW2XTvMlFMPRm9od8T58CHOvzy9yucQzO1upEemIZN_5XEtxICcL8jKzAgq9mqimAbYsw_oRhVtrutRwmANsmA1ACnODnknqCNw']
//        );
//        return "تمت العملية بنجاح";
//    }catch (\Exception $e)
//    {
//        return response()->json([
//            'title' => "خطا اتصال من الشبكة!",
//            'body' => $e->getMessage(),
//        ], 422);
//    }
});
/*
 * Route::get('/search', ...)
    ->middleware('throttle:30,1'); //max.minutes
 */
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
