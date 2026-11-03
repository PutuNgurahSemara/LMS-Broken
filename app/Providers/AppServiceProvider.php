<?php

namespace App\Providers;

use App\Events\AssignmentCreated;
use App\Listeners\SendAssignmentNotificationListener;
use Illuminate\Support\Facades\Event;
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
        // PERBAIKAN: Impor class secara rapi dan pastikan pendengaran event mengarah ke Queue Listener
        Event::listen(
            AssignmentCreated::class,
            SendAssignmentNotificationListener::class,
        );
    }
}