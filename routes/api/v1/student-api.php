<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\AuthController as StudentAuthController;
use App\Http\Controllers\Student\VerificationController as StudentVerificationController;
use App\Http\Controllers\Student\ForgotPasswordController as StudentForgotPasswordController;
use App\Http\Controllers\V2\StudentActivitiesController as V2StudentActivitiesController;
use App\Http\Controllers\Student\StudentMentorController;
use App\Http\Controllers\Student\MediaController;
use App\Http\Controllers\StudentActivitiesController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\Student\ProgrammeDetailController as StudentProgrammeDetailController;
use App\Http\Controllers\Student\InterestController;
use App\Http\Controllers\Student\CompetitionController;
use App\Http\Controllers\Student\AcademicController;
use App\Http\Controllers\Student\TodosController as StudentTodosController;
use App\Http\Controllers\Student\UniversityController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\Student\GroupController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CRM\ClientController;
use App\Http\Controllers\AuthController;

//* Authentication using Media Social
Route::get('login/{provider}', [AuthController::class, 'redirect']);
Route::get('login/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

//* check authentication
Route::get('auth/check', [StudentAuthController::class, 'check']);  

Route::get('crm/{role}/{type}', [ClientController::class, 'synchronize']);
Route::get('test/sync', [ClientController::class, 'import_student']);
Route::get('payment-checker', [TransactionController::class, 'payment_checker']);
Route::get('social-media/{person}/{id}', [SocialMediaController::class, 'index']);
Route::get('student/group/project/meeting/{encrypted_data}', [GroupController::class, 'attended'])->name('attend');

// authentication register & log-in
Route::prefix('auth/s')->group(function() {
    Route::post('register', [StudentAuthController::class, 'register']);
    Route::post('login', [StudentAuthController::class, 'authenticate']);
});

Route::get('verification/{verification_code}', [StudentVerificationController::class, 'verifying']);
Route::post('send/verification-code', [StudentVerificationController::class, 'resendVerificationCode'])->middleware(['auth:student-api']);
Route::post('reset-password', [StudentForgotPasswordController::class, 'sendResetPasswordLink']);
Route::post('reset-password/{token}', [StudentForgotPasswordController::class, 'ResetPassword']);

Route::group( ['prefix' => 'student', 'middleware' => ['auth:student-api', 'scopes:student'] ], function(){

    // list
    Route::get('dashboard/summarize', [V2StudentActivitiesController::class, 'index_student_count']);
    Route::get('mentor/list', [StudentMentorController::class, 'list']);
    Route::get('media/list', [MediaController::class, 'index_by_student']);
    Route::get('activities/{programme}/{recent?}', [StudentActivitiesController::class, 'index_by_student']);
    Route::get('list/{programme}/categories', [EventCategoryController::class, 'index']);
    Route::get('programme/detail/{programme}/{category?}', [StudentProgrammeDetailController::class, 'index']);
    Route::get('interest', [InterestController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
    Route::get('competition', [CompetitionController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
    Route::get('academic', [AcademicController::class, 'index']); //* use parameter mail for admin / mentor scopes & Need to moved to mentor, students scopes
    Route::get('todos', [StudentTodosController::class, 'index']);
    
    Route::get('university/requirement/{category}/{show_item?}', [UniversityController::class, 'index_requirement']);

    // find or select
    Route::get('appoinment/{mentor_id}', [StudentMentorController::class, 'find']);
    Route::get('programme/view/detail/{prog_dtl_id}', [StudentProgrammeDetailController::class, 'find']);

    // add
    Route::post('media/add', [MediaController::class, 'store']);
    Route::post('add/social-media', [SocialMediaController::class, 'store']);
    Route::post('make/{activities}', [StudentActivitiesController::class, 'store_by_student']);
    Route::post('interest', [InterestController::class, 'store']);
    Route::post('competition', [CompetitionController::class, 'store']);
    Route::post('academic', [AcademicController::class, 'store']);
    Route::post('group/project', [GroupController::class, 'store']);
    Route::post('group/project/meeting', [GroupController::class, 'create_meeting']);
    Route::post('academic/requirement', [UniversityController::class, 'store_academic_requirement']);
    Route::post('document/requirement', [UniversityController::class, 'store_document_requirement']);
    
    // change
    Route::post('change/profile-picture', [ProfileController::class, 'change_profile_picture']);
    Route::put('update/watch/{std_act_id}', [StudentActivitiesController::class, 'watch_time']);
    Route::post('upload/payment-proof', [TransactionController::class, 'upload_payment_proof']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::put('change/password', [ProfileController::class, 'change_password']);
    Route::post('update/social-media', [SocialMediaController::class, 'update']);
    Route::put('interest/{interest_id}', [InterestController::class, 'update']);
    Route::put('competition/{comp_id}', [CompetitionController::class, 'update']);
    Route::put('academic/{aca_id}', [AcademicController::class, 'update']);
    Route::put('group/project/{group_id}', [GroupController::class, 'update']);
    Route::put('group/project/participant/{group_id}', [GroupController::class, 'update_participant_role_contribution']);
    Route::post('group/project/confirmation/{status?}', [GroupController::class, 'confirmation_invitee'])->name('invitee-confirmation');
    Route::put('document/requirement/{med_id}', [UniversityController::class, 'update_document_requirement']);
    Route::post('media/pair', [MediaController::class, 'pair']);
    Route::post('media/update', [MediaController::class, 'update']);

    // delete
    Route::delete('media/delete/{media_id}', [MediaController::class, 'delete']);
    Route::delete('delete/social-media/{soc_med_id}', [SocialMediaController::class, 'delete']); 
    Route::delete('interest/{interest_id}', [InterestController::class, 'delete']);
    Route::delete('competition/{comp_id}', [CompetitionController::class, 'delete']);
    Route::delete('academic/{aca_id}', [AcademicController::class, 'delete']);
    Route::delete('academic/requirement/{academic_id}', [UniversityController::class, 'delete_academic_requirement']);

    Route::post('logout', [StudentAuthController::class, 'logout']);
    
});