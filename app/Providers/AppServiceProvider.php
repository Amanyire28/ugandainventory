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

        // Global Audit Logging for Logins and Logouts
        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            if ($event->user instanceof \App\Models\Admin) {
                \App\Models\AuditLog::create([
                    'admin_id' => $event->user->id, 'action' => 'admin_login',
                    'model' => \App\Models\Admin::class, 'model_id' => $event->user->id,
                    'new_values' => ['ip' => request()->ip(), 'user_agent' => request()->userAgent()],
                    'timestamp' => now(),
                ]);
            } elseif ($event->user instanceof \App\Models\User) {
                \App\Models\AuditLog::create([
                    'user_id' => $event->user->id, 'action' => 'user_login',
                    'model' => \App\Models\User::class, 'model_id' => $event->user->id,
                    'new_values' => ['ip' => request()->ip(), 'user_agent' => request()->userAgent()],
                    'timestamp' => now(),
                ]);
            }
        });

        \Illuminate\Support\Facades\Event::listen(function (\Illuminate\Auth\Events\Logout $event) {
            if ($event->user instanceof \App\Models\Admin) {
                \App\Models\AuditLog::create([
                    'admin_id' => $event->user->id, 'action' => 'admin_logout',
                    'model' => \App\Models\Admin::class, 'model_id' => $event->user->id,
                    'new_values' => ['ip' => request()->ip()],
                    'timestamp' => now(),
                ]);
            } elseif ($event->user instanceof \App\Models\User) {
                \App\Models\AuditLog::create([
                    'user_id' => $event->user->id, 'action' => 'user_logout',
                    'model' => \App\Models\User::class, 'model_id' => $event->user->id,
                    'new_values' => ['ip' => request()->ip()],
                    'timestamp' => now(),
                ]);
            }
        });
    }
}
