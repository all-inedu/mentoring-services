<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Google\GoogleCalendarController;
use App\Http\Controllers\MailLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ProgrammeModuleController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\Student\AuthController as StudentAuthController;
use App\Http\Controllers\Student\ForgotPasswordController as StudentForgotPasswordController;
use App\Http\Controllers\Student\VerificationController as StudentVerificationController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\UserRolesController;
use App\Http\Controllers\UserScheduleController;
use App\Http\Controllers\VerificationController;

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

Route::prefix('v1')->group(function(){

    //! Student Auth
    //* Authentication using Media Social
    Route::get('login/{provider}', [AuthController::class, 'redirect']);
    Route::get('login/{provider}/callback', [AuthController::class, 'handleProviderCallback']);
    
    Route::prefix('auth/s')->group(function() {
        Route::post('register', [StudentAuthController::class, 'register']);
        Route::post('login', [StudentAuthController::class, 'authenticate']);
    });
    Route::get('verification/{verification_code}', [StudentVerificationController::class, 'verifying']);
    Route::post('send/verification-code', [StudentVerificationController::class, 'resendVerificationCode'])->middleware(['auth:student-api']);
    Route::post('reset-password', [StudentForgotPasswordController::class, 'sendResetPasswordLink']);
    Route::post('reset-password/{token}', [StudentForgotPasswordController::class, 'ResetPassword']);

    Route::group( ['prefix' => 'student', 'middleware' => ['auth:student-api', 'scopes:student'] ], function(){
         
        
    });

    //! User Auth
    Route::prefix('auth/u')->group(function() {
        Route::post('register', [UserController::class, 'store']);
        Route::get('verification/{verification_code}', [UserController::class, 'verifying']);
        Route::post('send/verification-code', [UserController::class, 'resendVerificationCode'])->middleware(['auth:api']);
        Route::post('login', [UserController::class, 'authenticate']);
    });

    //! Global Scopes
    Route::middleware(['auth:api,student-api'])->group(function() {
        
        Route::get('{user}/schedule/{user_sch_id}', [UserScheduleController::class, 'find']); //user = mentor, alumni, editor

        Route::get('programme/{prog_id}', [ProgrammeController::class, 'find']);
    });

    //! Admin Scopes
    Route::middleware(['auth:api', 'scopes:admin'])->group(function() {
        
        Route::get('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'find']);
        Route::get('programme/{prog_id}', [ProgrammeController::class, 'find']);
        Route::get('promotion/validate/{promo_code}', [PromotionController::class, 'check_validation']);

        Route::prefix('switch')->group(function() {
            Route::post('promotion/{status}', [PromotionController::class, 'switch']);
        });

        Route::prefix('list')->group(function() {
            Route::get('mail/log', [MailLogController::class, 'index']);
            Route::get('programme/module', [ProgrammeModuleController::class, 'index']);
            Route::get('programme', [ProgrammeController::class, 'index']);
            Route::get('user/{user?}', [UserController::class, 'index']); //user = mentor, alumni, editor
            Route::get('promotion', [PromotionController::class, 'index']);
        });

        Route::prefix('select')->group(function() {
            Route::get('programme/use/programme-module/{prog_mod_id}', [ProgrammeController::class, 'select']);
        });

        Route::prefix('create')->group(function() {
            Route::post('permission', [PermissionController::class, 'store']);
            Route::post('role/assignment', [UserRolesController::class, 'store']);
            Route::post('programme/module', [ProgrammeModuleController::class, 'store']);
            Route::post('programme', [ProgrammeController::class, 'store']);
            Route::post('promotion', [PromotionController::class, 'store']);
        });

        Route::prefix('update')->group(function() {
            Route::put('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'update']);
            Route::put('programme/{prog_id}', [ProgrammeController::class, 'update']);
        });

        Route::prefix('delete')->group(function() {
            Route::delete('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'delete']);
            Route::delete('programme/{prog_id}', [ProgrammeController::class, 'delete']);
            Route::get('promotion/{promo_id}', [PromotionController::class, 'delete']);
        });
    });

    //! Mentor Scopes
    Route::middleware(['auth:api', 'scopes:mentor'])->group(function() {
        Route::prefix('create')->group(function() {
            Route::post('schedule', [UserScheduleController::class, 'store']);
        });

        Route::prefix('delete')->group(function() {
            Route::delete('schedule/{schedule_id}', [UserScheduleController::class, 'delete']);
        });
    });    
});