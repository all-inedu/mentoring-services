<?php

use App\Http\Controllers\TransactionController;
use App\Models\Medias;
use App\Models\StudentActivities;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailHandlerController;
use App\Providers\RouteServiceProvider;

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

Route::get('mail/test/add/meeting', function() {
    $data = [
        'prog_id' => 1, 
        'student_id' => 1,
        'user_id' => 3,
        'std_act_status' => 'confirmed',
        'mt_confirm_status' => 'waiting',
        'handled_by' => NULL, //! for now set to null
        'location_link' => "http://zoom.com/123",
        'location_pw' => '123',
        'prog_dtl_id' => NULL,
        'call_with' => 'mentor',
        'module' => 'life skills',
        'call_date' => '2022-06-10 10:20',
        'call_status' => 'waiting',
        'name' => 'Eric',
        'mentor' => array(
            'name' => 'Hedon'
        )
    ];

    return view('templates.mail.to-mentees.next-meeting-announcement', $data);
});

Route::get('mail/review', function() {
    $data['group_info'] = [
        'hyperlink' => RouteServiceProvider::ONGOING_PROJECT_DETAIL_HYPERLINK,
        'group_detail' => array(
            'project_id' => 53,
            'project_name' => 'Project Review',
            'project_type' => 'Group Project',
            'project_desc' => 'Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum',
            'project_owner' => 'Eric'
        )
        ];

    return view('templates.mail.to-mentors.invitation-group-project', $data);
});