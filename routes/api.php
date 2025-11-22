<?php

use App\Http\Controllers\Auth\UserController;
use Illuminate\Http\Request;
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
        Route::post('/resendPasswordResetOtp' , [UserController::class . 'resendPasswordResetOtp']);

    });

    Route::middleware("auth:api" )->middleware("role:citizen")->middleware("throttle:roleBasedApi")->group(function () {
        Route::get('/logout' , [UserController::class , 'logout']);
    });
});


/*
 * Route::get('/search', ...)
    ->middleware('throttle:30,1'); //max.minutes
 */
