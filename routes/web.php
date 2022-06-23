<?php

use App\Http\Controllers\TransactionController;
use App\Models\Medias;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
Route::get('transaction/{trx_id}/{type}', [TransactionController::class, 'invoice']);

Route::group(['middleware' => ['web']], function () {
    
    Route::get('/login', function() {
        return view('login');
    })->name('login');

    Route::get('reset-password/{token}', function($token) {
        return view('templates.mail.reset-password', ['token' => $token]);
    });
});

Route::get('coba', function () {
    echo '<div style="background:red;" ><iframe width="862" height="450" src="https://www.youtube.com/embed/mHA4BxZTXlk?start=600" title="Teaser Weekend: Top University Admission Mentoring" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
});
