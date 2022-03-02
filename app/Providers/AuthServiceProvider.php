<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Passport::routes();
        Passport::setDefaultScope([
            'student',
            'user',
        ]);
        Passport::tokensCan([
            'user' => 'Access User App',
            'student' => 'Access Student App',
            'admin' => 'Access Admin Menu',
            'mentor' => 'Access Mentor Menu',
            'editor' => 'Access Editor Menu',
            'alumni' => 'Access Alumni Menu',
            'super-admin' => 'Access Admin, Mentor, Editor, Alumni Menu'
        ]);

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        // Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        Passport::personalAccessTokensExpireIn(now()->addDays(7));
    }
}
