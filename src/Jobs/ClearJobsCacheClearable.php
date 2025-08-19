<?php

namespace Imagina\Icore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Jobs\ClearAllResponseCache;
use Jobs\ClearCacheByRoutes;

class ClearJobsCacheClearable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * vars
     */
    private $log;

    /**
     *
     */
    public function __construct()
    {
        $this->log = 'Core: Jobs||ClearJobsCacheClearable|';
    }

    /**
     * Execute the job.
     */
    public function handle()
    {

        $deleted = \DB::table('jobs')->where('queue', 'cacheByRoutes')->delete();
        \Log::info($this->log . "Deleted: " . $deleted);

        //Apply ClearAllResponseCache (JOB) and clean the home
        initProcessCache();
    }
}
