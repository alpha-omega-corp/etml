<?php

namespace App\Providers;

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // The header offers the administrator mode on every page, so every
        // view needs to know whether it is on.
        View::composer('*', function ($view) {
            $view->with('isAdmin', session(AdminController::SESSION_KEY) === true);
        });
    }
}
