<?php


namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->booted(function () {

            $schedule = $this->app->make(Schedule::class);
            
            /*
            $schedule->call(function () {
                \Modules\Core\Jobs\ClearJobsCacheClearable::dispatch();
            })->hourly();
            */

        });

    }
}
