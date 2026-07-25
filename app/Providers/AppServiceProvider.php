<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\View::composer('welcome', function ($view) {
            $view->with('packages', \App\Models\Package::where('is_active', true)->orderBy('price')->get());
            $view->with('businessCategories', \App\Models\BusinessCategory::where('is_active', true)->orderBy('name')->get());
        });
    }
}
