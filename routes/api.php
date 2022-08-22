<?php

use App\Http\Controllers\APController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\CRM\UniversityController as CRMUniversityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\EmailHandlerController;
use App\Http\Controllers\EssayController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\Google\GoogleCalendarController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\MailLogController;
use App\Http\Controllers\PartnershipController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\ProgrammeDetailController;
use App\Http\Controllers\ProgrammeModuleController;
use App\Http\Controllers\ProgrammeScheduleController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\SpeakerController;
use App\Http\Controllers\Student\AcademicController;
use App\Http\Controllers\Student\AuthController as StudentAuthController;
use App\Http\Controllers\Student\CompetitionController;
use App\Http\Controllers\Student\ForgotPasswordController as StudentForgotPasswordController;
use App\Http\Controllers\Student\GroupController;
use App\Http\Controllers\Student\InterestController;
use App\Http\Controllers\Student\MediaController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ProgrammeDetailController as StudentProgrammeDetailController;
use App\Http\Controllers\Student\StudentMentorController;
use App\Http\Controllers\Student\TodosController as StudentTodosController;
use App\Http\Controllers\Student\UniversityController;
use App\Http\Controllers\Student\VerificationController as StudentVerificationController;
use App\Http\Controllers\StudentActivitiesController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPairingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\User\GroupMeetingController;
use App\Http\Controllers\User\GroupProjectController;
use App\Http\Controllers\User\MeetingMinuteController;
use App\Http\Controllers\User\ParticipantController;
use App\Http\Controllers\User\TodosController;
use App\Http\Controllers\User\UniRequirementController;
use App\Http\Controllers\User\UniShortlistedController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\UserRolesController;
use App\Http\Controllers\UserScheduleController;
use App\Http\Controllers\V2\ProgrammeController as V2ProgrammeController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;
use App\Http\Controllers\VerificationController;
use App\Models\StudentActivities;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\V2\DashboardController as V2DashboardController;

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

// api route for mentee
Route::prefix('v1')->group(__DIR__ . '/api/v1/student-api.php');

// api route for user
Route::prefix('v1')->group(__DIR__ . '/api/v1/user-api.php');
Route::prefix('v2')->group(__DIR__ . '/api/v2/user-api.php');

// api route for global such as (mentee and user)
Route::prefix('v1')->group(function(){

    Route::post('mentees/pairing', [StudentPairingController::class, 'pair_student']);

    // email handler
    Route::post('mail/handler/confirm-meeting', [EmailHandlerController::class, 'mentee_confirm_meeting'])->name('confirm_meeting_from_email');
    
    Route::post('account/{user_type}/new-password', [UserController::class, 'store_new_password']);
    Route::middleware(['auth:api,student-api', 'scope:student,admin,mentor,editor'])->group(function() {
        
        Route::get('{user}/schedule/{user_sch_id}', [UserScheduleController::class, 'find']); //user = mentor, alumni, editor
        Route::get('programme/{prog_id}', [ProgrammeController::class, 'find']);
        Route::get('ap/list', [APController::class, 'index']);

        // change
        Route::put('{person}/confirmation/activities/{std_act_id}', [StudentActivitiesController::class, 'confirmation_personal_meeting']);
        Route::put('{person}/{status}/activities/{std_act_id}', [StudentActivitiesController::class, 'cancel_reject_personal_meeting']);
        
        Route::get('{person}/list/activities/{programme}/{status?}/{recent?}', [V2StudentActivitiesController::class, 'index_by_student']);
        Route::put('{person}/group/project/meeting/{meeting_id}', [GroupController::class, 'cancel_meeting']);
        Route::get('{person}/detail/group/project/{group_id}/{student_id?}', [GroupController::class, 'find']);

        Route::prefix('list')->group(function () {
            Route::get('programme/{type?}', [ProgrammeController::class, 'index']);
        });

        Route::prefix('switch')->group(function () {
            Route::post('todos', [TodosController::class, 'switch']);
        });

        Route::prefix('student')->group(function() {
            Route::get('group/project/{status}/{student_id?}', [GroupController::class, 'index']);
            Route::post('group/project/participant', [GroupController::class, 'add_participant']);
            Route::delete('group/project/participant/{group_id}/{student_id}', [GroupController::class, 'remove_participant']);
            Route::get('university/shortlisted/{status}/{student_id?}', [UniversityController::class, 'index']);
        });
    });
});

Route::prefix('v2')->group(function() {
    //! GLOBAL SCOPES v2
    Route::middleware(['auth:api', 'scope:admin,mentor,editor', 'cors'])->group(function() {
        Route::get('overview/{role}/total', [V2DashboardController::class, 'overview']);
        
    });
});
