<?php

use App\Http\Controllers\V2\ProgrammeDetailController as V2ProgrammeDetailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V2\DashboardController as V2DashboardController;
use App\Http\Controllers\V2\ProgrammeController as V2ProgrammeController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;
use App\Http\Controllers\V2\StudentController as V2StudentController;

//! Admin Scopes v2
Route::middleware(['auth:api', 'scopes:admin'])->group(function() {

    Route::prefix('list')->group(function() {
        Route::get('programme/{type}', [V2ProgrammeController::class, 'index']);
        Route::get('students', [V2StudentController::class, 'index']);
        Route::get('admin/students', [V2StudentController::class, 'select_all']);
    });

    Route::prefix('create')->group(function() {
        Route::post('programme', [V2ProgrammeController::class, 'store']);
    });

    Route::prefix('update')->group(function() {
        Route::put('programme/{prog_id}', [V2ProgrammeController::class, 'update']);
        Route::put('programme/detail/{prog_dtl_id}', [V2ProgrammeDetailController::class, 'update']);
    });
});

//! Mentor Scopes
Route::middleware(['auth:api', 'scope:mentor,admin'])->group(function() {

    Route::prefix('update')->group(function() {
        Route::put('status/1-on-1-call', [V2StudentActivitiesController::class, 'finish_meeting']);
    });

    Route::prefix('list')->group(function() {
        Route::get('activities/{programme}/{status}/{recent?}', [V2StudentActivitiesController::class, 'index']);
        Route::get('meeting-log/{student_id}', [V2StudentActivitiesController::class, 'meeting_log']);
    });

    Route::prefix('create')->group(function() {
        Route::post('activities/1-on-1-call', [V2StudentActivitiesController::class, 'store']);
    });
});