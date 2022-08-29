<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\EssayController;
use App\Http\Controllers\ProgrammeModuleController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\Student\ProgrammeDetailController;
use App\Http\Controllers\ProgrammeScheduleController;
use App\Http\Controllers\SpeakerController;
use App\Http\Controllers\PartnershipController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Student\MediaController;
use App\Http\Controllers\MailLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\StudentActivitiesController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\UserRolesController;
use App\Http\Controllers\Student\StudentMentorController;
use App\Http\Controllers\User\UniShortlistedController;
use App\Http\Controllers\CRM\UniversityController as CRMUniversityController;
use App\Http\Controllers\User\UniRequirementController;
use App\Http\Controllers\User\GroupProjectController;
use App\Http\Controllers\User\GroupMeetingController;
use App\Http\Controllers\User\TodosController;
use App\Http\Controllers\User\MeetingMinuteController;
use App\Http\Controllers\User\ParticipantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupProjectController as AdminGroupProjectController;
use App\Http\Controllers\ProgrammeDetailController as AdminProgrammeDetailController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\UserScheduleController;
use App\Http\Controllers\V2\ProgrammeController as V2ProgrammeController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;

Route::prefix('auth/u')->group(function() {
    Route::get('check/token', [UserController::class, 'check_token'])->middleware(['auth:api', 'scopes:mentor']);
    Route::post('register', [UserController::class, 'store']);
    Route::get('verification/{verification_code}', [UserController::class, 'verifying']);
    Route::post('send/verification-code', [UserController::class, 'resendVerificationCode'])->middleware(['auth:api']);
    Route::post('login', [UserController::class, 'authenticate']);
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
        Route::get('view/webinar/{webinar_id}', [AdminProgrammeDetailController::class, 'viewer']);
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
        // Route::get('programme/{type?}', [ProgrammeController::class, 'index']); //? di hide karena ada version 2 yang include di file ini (do not delete)
        Route::get('role', [PermissionController::class, 'index']);
        Route::get('user/{role_name?}', [UserController::class, 'index']); //user = mentor, alumni, editor
        Route::get('promotion', [PromotionController::class, 'index']);
        Route::get('speaker', [SpeakerController::class, 'index']);
        Route::get('student', [StudentController::class, 'index']);
        Route::get('activities/{programme}/{recent?}', [StudentActivitiesController::class, 'index']);
        Route::get('transaction/{status}/{recent?}', [TransactionController::class, 'index']);
        Route::get('social-media/{person}/{id}', [SocialMediaController::class, 'index']);
        Route::get('student/files', [MediaController::class, 'index']);

        Route::get('todos', [TodosController::class, 'index']);
        Route::get('group-project', [AdminGroupProjectController::class, 'index']);
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
        // Route::post('programme', [ProgrammeController::class, 'store']); //? di hide karena ada version 2 yang include di file ini (do not delete)
        Route::post('programme/schedule', [ProgrammeScheduleController::class, 'store']);
        Route::post('programme/detail', [AdminProgrammeDetailController::class, 'store']);
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
        // Route::put('programme/{prog_id}', [ProgrammeController::class, 'update']); //? di hide karena ada version 2 yang include di file ini (do not delete)
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
        Route::delete('programme/detail/{prog_dtl_id}', [AdminProgrammeDetailController::class, 'delete']);
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
Route::middleware(['auth:api', 'scope:admin,mentor'])->group(function() {
    Route::get('student/list', [StudentController::class, 'select_by_auth']);
    Route::get('student/detail/{student_id?}', [StudentController::class, 'index']);
    Route::get('student/files', [MediaController::class, 'index']);

    Route::prefix('switch')->group(function() {
        Route::post('shortlisted/{status?}', [UniShortlistedController::class, 'switch']);
    });

    Route::prefix('list')->group(function() {
        Route::get('country/university', [CRMUniversityController::class, 'country']);
        Route::get('university/{country?}', [CRMUniversityController::class, 'index']);
        Route::get('requirement/{category}/{student_id}/{univ_id?}', [UniRequirementController::class, 'index']);
        Route::get('mentor/group/project/{status}', [GroupProjectController::class, 'index']);
        Route::get('group/meeting/{status}/{recent?}', [GroupMeetingController::class, 'index']);
    });

    Route::prefix('find')->group(function() {
        
    });

    Route::prefix('select')->group(function() {
        Route::get('shortlisted/{student_id}/{all?}', [UniShortlistedController::class, 'select']);
        Route::get('todos/{student_id}', [TodosController::class, 'select']);
        Route::get('meeting-minutes/{st_act_id}', [MeetingMinuteController::class, 'select']);
        
    });

    Route::prefix('create')->group(function() {
        Route::post('shortlisted', [UniShortlistedController::class, 'store']);
        Route::post('group/project', [GroupProjectController::class, 'store']);
        Route::post('group/meeting', [GroupMeetingController::class, 'store']);
        Route::post('todos', [TodosController::class, 'store']);
        Route::post('meeting-minutes', [MeetingMinuteController::class, 'store']);
        Route::post('group/participant', [ParticipantController::class, 'store']);
    });

    Route::prefix('update')->group(function() {
        Route::put('student/{student_id}/{profile_column}', [StudentController::class, 'profile']);
        Route::put('group/project/{group_id}', [GroupProjectController::class, 'update']);
        Route::put('{field}/group/project/{group_id}', [GroupProjectController::class, 'update_field']);
        Route::put('progress/status/group/project/{group_id}/{progress}', [GroupProjectController::class, 'update_progress']);
        Route::put('group/meeting/attendance/{group_meet_id}', [GroupMeetingController::class, 'attended']);
    });

    Route::prefix('delete')->group(function() {
        Route::delete('todos/{todos_id}', [TodosController::class, 'delete']);
        Route::delete('shortlisted/{uni_shortlisted_id}', [UniShortlistedController::class, 'delete']);
    });

    Route::get('mentor/meeting/summary', [V2StudentActivitiesController::class, 'mentors_meeting_summary']);
    Route::get('mentor/group-projects/summary', [V2StudentActivitiesController::class, 'mentors_group_project_summary']);
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
        Route::post('tag', [TagsController::class, 'store']);
    });

    Route::prefix('update')->group(function() {
        Route::put('tag/{tag_id}', [TagsController::class, 'update']);
    });

    Route::prefix('list')->group(function() {
        Route::get('tag', [TagsController::class, 'index']);
    });

    Route::prefix('delete')->group(function() {
        Route::delete('schedule/{schedule_id}', [UserScheduleController::class, 'delete']);
        Route::delete('tag/{tag_id}', [TagsController::class, 'delete']);
    });
});