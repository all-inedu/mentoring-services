<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

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

Route::prefix('v1')->group(function () {
    Route::get('test', [AuthController::class, 'test']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login'])->name('login');

    Route::group(['middleware' => ['jwt.verify']], function () {
        Route::get('user', [UserController::class, 'user']);
    });
});


// Route::get('userall', 'UserController@userAuth')->middleware('jwt.verify');
// Route::get('user', 'AuthController@getAuthenticatedUser')->middleware('jwt.verify');
