<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
// use Illuminate\Notifications\Messages\MailMessage;

use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enable Passport routes
        Passport::enablePasswordGrant();
        PermissionServiceProvider::class;

        if ($this->app->environment() !== 'production') {
            VerifyEmail::toMailUsing(function (object $notifiable, string $url) {

            });
        }
    }
}
