<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V2\DashboardController as V2DashboardController;
use App\Http\Controllers\V2\ProgrammeController as V2ProgrammeController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;

//! Admin Scopes v2
Route::middleware(['auth:api', 'scopes:admin'])->group(function() {

    Route::prefix('list')->group(function() {
        Route::get('programme/{type}', [V2ProgrammeController::class, 'index']);
    });

    Route::prefix('create')->group(function() {
        Route::post('programme', [V2ProgrammeController::class, 'store']);
    });

    Route::prefix('update')->group(function() {
        Route::put('programme/{prog_id}', [V2ProgrammeController::class, 'update']);
    });
});

//! Mentor Scopes
Route::middleware(['auth:api', 'scopes:mentor'])->group(function() {

    Route::prefix('list')->group(function() {
        Route::get('activities/{programme}/{status}/{recent?}', [V2StudentActivitiesController::class, 'index']);
    });

    Route::prefix('create')->group(function() {
        Route::post('activities/1-on-1-call', [V2StudentActivitiesController::class, 'store']);
    });
});