<?php

use App\Http\Controllers\Agency_Domain\AgencyController;
use App\Http\Controllers\Audit\NotificationController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Complaints_Domain\ComplaintController;
use App\Http\Controllers\Complaints_Domain\ComplaintTypeController;
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

        Route::get('/Notification' , [NotificationController::class , 'getCitizenNotifications']);
    });
});

Route::post('/refresh' , [UserController::class , 'refresh'])->middleware('jwt.refresh');

/*
 * Route::get('/search', ...)
    ->middleware('throttle:30,1'); //max.minutes
 */
