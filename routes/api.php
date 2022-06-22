<?php

use App\Http\Controllers\APController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\EssayController;
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
use App\Http\Controllers\Student\StudentMentorController;
use App\Http\Controllers\Student\UniversityController;
use App\Http\Controllers\Student\VerificationController as StudentVerificationController;
use App\Http\Controllers\StudentActivitiesController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\UserRolesController;
use App\Http\Controllers\UserScheduleController;
use App\Http\Controllers\V2\ProgrammeController as V2ProgrammeController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;
use App\Http\Controllers\VerificationController;
use App\Models\StudentActivities;
use Illuminate\Support\Facades\Crypt;

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

Route::get('a', function() {
    $data = array(
        'name' => 'eric',
        'group_info' => (object) array(
            'project_name' => 'project AB'
        ),
        'meeting_detail' => (object) array(
            'meeting_subject' => 'subject AB',
            'meeting_date' => '2022-06-09 12:00:00',
            'meeting_link' => 'https://zoom.us/123',
            'token' => Crypt::encrypt(array(
                'nama' => 'eric'
            ))
        )
    );
    return view('templates/mail/cancel-group-meeting-announcement', $data);
});

Route::prefix('v1')->group(function(){
    
    // Route::get('daily/mail/error', [MailLogController::class, 'mail_to_tech']);
    Route::get('crm/{role}/{type}', [ClientController::class, 'synchronize']);
    Route::get('test/sync', [ClientController::class, 'import_student']);
    Route::get('payment-checker', [TransactionController::class, 'payment_checker']);

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
         
        Route::post('logout', [StudentAuthController::class, 'logout']);
        Route::post('media/add', [MediaController::class, 'store']);
        Route::delete('media/delete/{media_id}', [MediaController::class, 'delete']);
        Route::get('media/list', [MediaController::class, 'index_by_student']);
        Route::post('upload/payment-proof', [TransactionController::class, 'upload_payment_proof']);
        Route::get('activities/{programme}/{recent?}', [StudentActivitiesController::class, 'index_by_student']);
        Route::get('mentor/list', [StudentMentorController::class, 'list']);
        Route::get('appoinment/{mentor_id}', [StudentMentorController::class, 'find']);
        Route::post('add/social-media', [SocialMediaController::class, 'store']);
        Route::post('update/social-media', [SocialMediaController::class, 'update']);
        Route::delete('delete/social-media/{soc_med_id}', [SocialMediaController::class, 'delete']);   
        
        
        Route::post('make/{activities}', [StudentActivitiesController::class, 'store_by_student']);
        //** New */
        Route::put('confirmation/activities/{std_act_id}', [StudentActivitiesController::class, 'confirmation_personal_meeting']);
        Route::get('list/activities/{programme}/{status}/{recent?}', [V2StudentActivitiesController::class, 'index_by_student']);
        Route::put('cancel/activities/{std_act_id}', [StudentActivitiesController::class, 'cancel_personal_meeting']);

        //** New */
        Route::get('interest', [InterestController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
        Route::post('interest', [InterestController::class, 'store']);
        Route::put('interest/{interest_id}', [InterestController::class, 'update']);
        Route::delete('interest/{interest_id}', [InterestController::class, 'delete']);

        //* New */
        Route::get('competition', [CompetitionController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
        Route::post('competition', [CompetitionController::class, 'store']);
        Route::put('competition/{comp_id}', [CompetitionController::class, 'update']);
        Route::delete('competition/{comp_id}', [CompetitionController::class, 'delete']);

        //* New */
        Route::get('academic', [AcademicController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
        Route::post('academic', [AcademicController::class, 'store']);
        Route::put('academic/{aca_id}', [AcademicController::class, 'update']);
        Route::delete('academic/{aca_id}', [AcademicController::class, 'delete']);

        //* New */
        Route::put('profile', [ProfileController::class, 'update']);
        Route::put('change/password', [ProfileController::class, 'change_password']);

        //* New */
        Route::get('group/project/{status}', [GroupController::class, 'index']);
        Route::get('group/project/detail/{group_id}', [GroupController::class, 'find']);
        Route::post('group/project', [GroupController::class, 'store']);
        Route::put('group/project/{group_id}', [GroupController::class, 'update']);
        
        Route::post('group/project/participant', [GroupController::class, 'add_participant']);
        Route::put('group/project/participant/{group_id}', [GroupController::class, 'update_participant_role_contribution']);
        Route::delete('group/project/participant/{group_id}/{student_id}', [GroupController::class, 'remove_participant']);
        Route::post('group/project/confirmation/{status?}', [GroupController::class, 'confirmation_invitee'])->name('invitee-confirmation');

        Route::post('group/project/meeting', [GroupController::class, 'create_meeting']);
        Route::get('group/project/meeting/{encrypted_data}', [GroupController::class, 'attended'])->name('attend');
        Route::put('group/project/meeting/{meeting_id}', [GroupController::class, 'cancel_meeting']);

        //* New */
        Route::get('university/shortlisted/{status}', [UniversityController::class, 'index']);

        //* New */
        Route::get('university/requirement/{category}/{show_item?}', [UniversityController::class, 'index_requirement']);
        Route::post('academic/requirement', [UniversityController::class, 'store_academic_requirement']);
        Route::delete('academic/requirement/{academic_id}', [UniversityController::class, 'delete_academic_requirement']);
        Route::post('document/requirement', [UniversityController::class, 'store_document_requirement']);
        Route::put('document/requirement/{med_id}', [UniversityController::class, 'update_document_requirement']);
        Route::post('media/pair', [MediaController::class, 'pair']);

        
    });

    Route::get('social-media/{person}/{id}', [SocialMediaController::class, 'index'])->middleware('auth:student-api,api');

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

        //* New */
        Route::get('ap/list', [APController::class, 'index']);
    });

    Route::prefix('list')->group(function() {
        // Route::get('programme/{type?}', [ProgrammeController::class, 'index'])->middleware('student-api');
        Route::get('programme/{type?}', function (Request $request) {
            return Auth::guard('student-api')->user();
        });
    });
    Route::get('transaction/{trx_id}/{type}', [TransactionController::class, 'invoice']);

    //! Admin Scopes
    Route::middleware(['auth:api', 'scopes:admin', 'cors'])->group(function() {
        
        Route::get('promotion/validate/{promo_code}', [PromotionController::class, 'check_validation']);
        Route::get('transaction/{trx_id}/{type}', [TransactionController::class, 'invoice']);
        Route::get('last/sync/{user_type}', [HelperController::class, 'last_sync']);
        Route::get('essay/{status}/{id}', [EssayController::class, 'count_essay']);
        Route::get('overview/transaction', [TransactionController::class, 'count_transaction']);

        Route::prefix('find')->group(function() {
            Route::get('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'find']);
            Route::get('programme/{prog_id}', [ProgrammeController::class, 'find']);
            Route::get('programme/detail/{prog_dtl_id}', [ProgrammeDetailController::class, 'find']);
            Route::get('programme/schedule/{prog_sch_id}', [ProgrammeScheduleController::class, 'find']);
            Route::get('programme/speaker/{sp_id}', [SpeakerController::class, 'find']);
            Route::get('programme/partner/{pt_id}', [PartnershipController::class, 'find']);
            Route::get('education/{edu_id}', [EducationController::class, 'find']);
            Route::get('user/{role_name}/{id?}', [UserController::class, 'find']); //find user by id & keyword
            Route::get('student', [StudentController::class, 'find']);
        });

        Route::prefix('switch')->group(function() {
            Route::post('programme/module/{status}', [ProgrammeModuleController::class, 'switch']);
            Route::post('programme/{status}', [ProgrammeController::class, 'switch']);
            Route::post('promotion/{status}', [PromotionController::class, 'switch']);
            Route::post('transaction/{status}', [TransactionController::class, 'switch']);
            Route::post('student/files/{file_id}', [MediaController::class, 'switch']);
        });

        Route::prefix('list')->group(function() {
            Route::get('mail/log/{param}', [MailLogController::class, 'index']);
            Route::get('programme/module', [ProgrammeModuleController::class, 'index']);
            // Route::get('programme/{type?}', [ProgrammeController::class, 'index']);
            Route::get('role', [PermissionController::class, 'index']);
            Route::get('user/{role_name?}', [UserController::class, 'index']); //user = mentor, alumni, editor
            Route::get('promotion', [PromotionController::class, 'index']);
            Route::get('speaker', [SpeakerController::class, 'index']);
            Route::get('student', [StudentController::class, 'index']);
            Route::get('activities/{programme}/{recent?}', [StudentActivitiesController::class, 'index']);
            Route::get('transaction/{status}/{recent?}', [TransactionController::class, 'index']);
            Route::get('social-media/{person}/{id}', [SocialMediaController::class, 'index']);

            Route::get('student/files', [MediaController::class, 'index']);
        });

        Route::prefix('select')->group(function() {
            Route::get('programme/use/programme-module/{prog_mod_id}', [ProgrammeController::class, 'select']);
            Route::get('permission/use/role/{role_id}', [PermissionController::class, 'select']);
            Route::get('programme-details/use/programme/{prog_id}', [ProgrammeDetailController::class, 'select']);
            Route::get('programme-schedule/use/programme-detail/{prog_dtl_id}', [ProgrammeScheduleController::class, 'select']);
            Route::get('speakers/use/programme-detail/{prog_dtl_id}', [SpeakerController::class, 'select']);
            Route::get('partners/use/programme-detail/{prog_dtl_id}', [PartnershipController::class, 'select']);
            Route::get('education/use/user/{user_id}', [EducationController::class, 'select']);
            Route::get('students/use/user/{user_id}', [StudentController::class, 'select']);
        });

        Route::prefix('create')->group(function() {
            Route::post('permission', [PermissionController::class, 'store']);
            Route::post('role/assignment', [UserRolesController::class, 'store']);
            Route::post('programme/module', [ProgrammeModuleController::class, 'store']);
            Route::post('programme', [ProgrammeController::class, 'store']);
            Route::post('programme/schedule', [ProgrammeScheduleController::class, 'store']);
            Route::post('programme/detail', [ProgrammeDetailController::class, 'store']);
            Route::post('speaker', [SpeakerController::class, 'store']);
            Route::post('partner', [PartnershipController::class, 'store']);
            Route::post('promotion', [PromotionController::class, 'store']);
            Route::post('mentor/assignment', [StudentMentorController::class, 'store']);
            Route::post('education', [EducationController::class, 'store']);
            // Route::post('student/activities', [StudentActivitiesController::class, 'store']);
            Route::post('social-media', [SocialMediaController::class, 'store']);
        });

        Route::prefix('update')->group(function() {
            Route::put('permission/{per_id}', [PermissionController::class, 'update']);
            Route::put('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'update']);
            Route::put('programme/{prog_id}', [ProgrammeController::class, 'update']);
            Route::put('programme/schedule/{prog_sch_id}', [ProgrammeScheduleController::class, 'update']);
            Route::put('programme/detail/{prog_dtl_id}', [ProgrammeDetailController::class, 'update']);
            Route::put('speaker/{sp_id}', [SpeakerController::class, 'update']);
            Route::put('partner/{pt_id}', [PartnershipController::class, 'update']);
            Route::put('education/{edu_id}', [EducationController::class, 'update']);
            Route::put('social-media/{soc_med_id}', [SocialMediaController::class, 'update']);
            Route::post('user/profile', [UserController::class, 'update']);
            Route::get('mail/log/{mail_id}', [MailLogController::class, 'update']);
        });

        Route::prefix('delete')->group(function() {
            Route::delete('programme/module/{prog_mod_id}', [ProgrammeModuleController::class, 'delete']);
            Route::delete('programme/{prog_id}', [ProgrammeController::class, 'delete']);
            Route::delete('programme/detail/{prog_dtl_id}', [ProgrammeDetailController::class, 'delete']);
            Route::delete('programme/schedule/{prog_sch_id}', [ProgrammeScheduleController::class, 'delete']);
            Route::get('promotion/{promo_id}', [PromotionController::class, 'delete']);
            Route::delete('role/assignment/{user_role_id}', [UserRolesController::class, 'delete']);
            Route::delete('speaker/{sp_id}', [SpeakerController::class, 'delete']);
            Route::delete('partner/{pt_id}', [PartnershipController::class, 'delete']);
            Route::delete('education/{edu_id}', [EducationController::class, 'delete']);
            Route::delete('social-media/{soc_med_id}', [SocialMediaController::class, 'delete']);
        });
    });

    //! Mentor Scopes
    Route::middleware(['auth:api', 'scopes:mentor'])->group(function() {
        Route::get('student/list', [StudentController::class, 'select_by_auth']);
        Route::get('student/detail', [StudentController::class, 'index']);
        Route::get('student/files', [MediaController::class, 'index']);
    });    

    //! Editor Scopes
    Route::middleware(['auth:api', 'scopes:editor'])->group(function() {
        Route::prefix('count')->group(function() {
            Route::get('essay', [EssayController::class, 'count_essay']);
        });
    });

    //! Alumni Scopes
    Route::middleware(['auth:api', 'scopes:alumni'])->group(function() {
        
    });

    //! Admin, Mentor, & Editor Scopes
    Route::middleware(['auth:api', 'scope:admin,mentor,editor', 'cors'])->group(function() {
        Route::get('overview/{role}/total', [DashboardController::class, 'overview']);
        Route::post('set/meeting', [StudentActivitiesController::class, 'set_meeting']);
        Route::get('activities/{programme}/{recent?}', [StudentActivitiesController::class, 'index_by_auth']);

        Route::prefix('create')->group(function() {
            Route::post('schedule', [UserScheduleController::class, 'store']);
        });

        Route::prefix('delete')->group(function() {
            Route::delete('schedule/{schedule_id}', [UserScheduleController::class, 'delete']);
        });
    });
});

Route::prefix('v2')->group(function() {
    
    //! Admin Scopes
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
});