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

    Route::post('/register' , [UserController::class , 'registerCitizen'])->middleware('throttle:registerApi');
    Route::post('/login' , [UserController::class , 'loginCitizen'])->middleware('throttle:loginApi');

    Route::post('/verifyAccount' , [UserController::class , 'verifyRegistrationCitizen']);
    Route::post('/resendOtp' , [UserController::class , 'resendOtp'])->middleware('throttle:registerApi');

    Route::middleware('throttle:forgotPasswordApi')->group(function () {

        Route::post('/forgotPassword' , [UserController::class , 'forgotPassword']);
        Route::post('/verifyForgotPasswordEmail' , [UserController::class , 'verifyForgotPasswordEmail']);
        Route::post('/resetPassword' , [UserController::class , 'resetPassword']);
        Route::post('/resendPasswordResetOtp' , [UserController::class , 'resendPasswordResetOtp']);

    });

    /** @noinspection PhpParamsInspection */
    Route::middleware(['jwt' ,'role:citizen' , 'throttle:roleBasedApi'])->group(function () {
        Route::get('/logout' , [UserController::class , 'logout']);

        Route::prefix('/home')->group(function () {

            Route::post('/showComplaints' , [ComplaintController::class , 'getCitizenComplaints']);
            Route::post('/searchComplaint' , [ComplaintController::class , 'SearchComplaint']);

            Route::post('/createComplain' , [ComplaintController::class , 'createCitizenComplaint']);
            Route::get('/agencyByName' , [AgencyController::class , 'agencies']);
            Route::get('/ComplaintTypeByName' , [ComplaintTypeController::class , 'complaintTypes']);
        });

        Route::prefix('/complaint')->group(function () {

            Route::get('/getDetails/{complain_id}' , [ComplaintController::class , 'getCitizenComplaintDetails']);
            Route::delete('/delete/{complain_id}' , [ComplaintController::class , 'deleteComplaint']);
            Route::post('/addDetails/{complain_id}' , [ComplaintController::class , 'addExtraInfoToComplaint']);
        });

        Route::get('/notification' , [NotificationController::class , 'getCitizenNotifications']);
    });
});

Route::prefix('/v1/both')->group(function () {

    Route::post('/login' , [UserController::class , 'loginForDashboard'])->middleware('throttle:loginApi');

    Route::middleware('throttle:forgotPasswordApi')->group(function () {

        Route::post('/forgotPassword' , [UserController::class , 'forgotPassword']);
        Route::post('/verifyForgotPasswordEmail' , [UserController::class , 'verifyForgotPasswordEmail']);
        Route::post('/resetPassword' , [UserController::class , 'resetPassword']);
        Route::post('/resendPasswordResetOtp' , [UserController::class , 'resendPasswordResetOtp']);

    });

    /** @noinspection PhpParamsInspection */
    Route::middleware(['jwt' ,'role:both' , 'throttle:roleBasedApi'])->group(function () {

        Route::get('/logout' , [UserController::class , 'logout']);

        Route::prefix('/home')->group(function () {


        });

        Route::prefix('/UserManagement')->group(function () {



        });

        Route::prefix('/ComplaintManagement')->group(function () {
            Route::post('/getComplaint' , [ComplaintController::class , 'getComplaintBasedRole']);
            Route::get('/getDetails/{complain_id}' , [ComplaintController::class , 'ComplaintDetails']);

            Route::post('/inProgress/{complain_id}' , [ComplaintController::class , 'StartProcessingComplaint']);
            Route::post('/reject/{complain_id}' , [ComplaintController::class , 'rejectComplaint']);
            Route::post('/finish/{complain_id}' , [ComplaintController::class , 'finishProcessingComplaint']);
            Route::post('/moreInfo/{complain_id}' , [ComplaintController::class , 'requestMoreInfoToComplaint']);
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
