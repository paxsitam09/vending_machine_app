<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

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
    public function boot()
    {
        // Make the authenticated user available globally
        View::composer('*', function ($view) {
            $view->with('authUser', Auth::user());
        });
  
        // Apply global role-based access control for certain routes
        Route::middleware(['web', 'auth'])->group(function () {
            Route::matched(function ($event) {
                // Retrieve user's role from the session
                $role = Session::get('role');
  
                // Get the current route name
                $routeName = $event->route->getName();
  
                // Define role-based restrictions
                if ($role === 'user' && $routeName === 'admin.dashboard') {
                    abort(403, 'Unauthorized access.');
                }
  
                if ($role === 'admin' && $routeName === 'user.dashboard') {
                    abort(403, 'Unauthorized access.');
                }
            });
        });
    }
}
