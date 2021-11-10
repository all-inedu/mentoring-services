<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ResetPasswordController;
use App\Http\Controllers\Api\V1\VerificationController;


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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1', 'middleware' => 'throttle:60,1'], function () {

    Route::get('view-email-template', function() {
        $token = 1021;
        return view("email/forgetPassword", ['token' => $token]);
    });

    //! Verification
    Route::post('email/verification-notification', [VerificationController::class, 'verificationNotification'])->middleware(['auth:api'])->name('verification.send');
    Route::get('user/verify/{verification_code}', [VerificationController::class, 'verifyUser'])->name('user.verify');

    //! Reset Password
    Route::get('password/reset/{token}', [ResetPasswordController::class, 'submitResetPassword'])->name('password.submit');
    Route::get('reset/{token}', [ResetPasswordController::class, 'handleResetPassword'])->name('password.request');
    Route::post('password/reset', [ResetPasswordController::class, 'sendResetPasswordLink'])->name('password.reset');

    //! Authentication
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => ['jwt.verify']], function () {
        Route::get('user/{type}', [UserController::class, 'user']);
    });
});


// Route::get('userall', 'UserController@userAuth')->middleware('jwt.verify');
// Route::get('user', 'AuthController@getAuthenticatedUser')->middleware('jwt.verify');
