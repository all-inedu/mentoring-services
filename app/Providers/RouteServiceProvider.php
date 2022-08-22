<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';
    public const SYSTEM_NAME = 'mentoring';
    public const RESET_PASSWORD_LINK = 'https://mentoring.all-inedu.com/reset/';
    public const TECH_MAIL_1 = 'manuel.eric@all-inedu.com';
    public const DAY_RANGE_ERROR_REPORT = 7;

    public const STUDENT_LIST_MEDIA_VIEW_PER_PAGE = 10;
    public const STUDENT_GROUP_PROJECT_VIEW_PER_PAGE = 4;
    public const STUDENT_UNIVERSITY_SHORTLISTED_VIEW_PER_PAGE = 10;
    public const STUDENT_MEETING_VIEW_PER_PAGE = 10;

    public const MENTOR_GROUP_PROJECT_VIEW_PER_PAGE = 4;
    public const MENTOR_MEETING_VIEW_PER_PAGE = 10;

    public const ADMIN_LIST_STUDENT_VIEW_PER_PAGE = 10;
    public const ADMIN_LIST_USER_VIEW_PER_PAGE = 10;
    public const ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = 10;
    public const ADMIN_LIST_PROMOTION_VIEW_PER_PAGE = 10;
    public const ADMIN_LIST_TRANSACTION_VIEW_PER_PAGE = 10;
    public const ADMIN_LIST_GROUP_PROJECT_ALL_PER_PAGE = 10;
    
    // public const USER_PUBLIC_ASSETS_PATH = 'storage/media';
    public const USER_PUBLIC_ASSETS_PAYMENT_PROOF_PATH = 'public/media/payment-proof';
    public const USER_STORE_MEDIA_PATH = 'public/media/system';
    public const USER_STORE_PROFILE_PATH = 'profile/mentor';
    public const STUDENT_STORE_MEDIA_PATH = 'public';

    //* mail info
    // 1 on 1 call 
    // reminder notification to mentees
    public const TO_MENTEES_1ON1CALL_SUBJECT = 'New Call Invitation';

    // 1 on 1 call
    // when mentee confirm invitee from mentor to join 
    public const TO_MENTORS_MENTEE_HAS_CONFIRM_1ON1CALL_SUBJECT = 'has confirmed the 1 on 1 Call';

    // 1 on 1 call
    // when mentee cancel meeting
    public const TO_MENTEES_CANCEL_1ON1CALL_SUBJECT = 'The meeting has been canceled';

    // 1 on 1 call
    // when mentee reject meeting
    public const TO_MENTORS_MENTEE_HAS_REJECT_1ON1CALL_SUBJECT = 'has unable to attend the meeting';

    // group project
    // when mentee create group project
    public const TO_MENTORS_GROUP_PROJECT_CREATED = 'New Group Project Has Been Made';

    public const ONGOING_PROJECT_DETAIL_HYPERLINK = 'https://mentoring.all-inedu.com/mentor/activity/group/in-progress/';

    public const NOTIFICATION_HANDLER = 'https://mentoring.all-inedu.com/notification/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */

    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute(500),
                Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip())
            ];
        });
    }
}
