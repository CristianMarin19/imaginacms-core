<?php

namespace Imagina\Icore\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Imagina\Icore\Routes\RouterGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IcoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register apiCrud as a router macro
        Route::macro('apiCrud', function ($params) {
            app(RouterGenerator::class)->apiCrud($params);
        });
        //Instance macro to Blueprint class to auditStamps
        Blueprint::macro('auditStamps', function () {
            //Deleted_at
            if (!Schema::hasColumn($this->getTable(), 'deleted_at')) {
                $this->timestamp('deleted_at', 0)->nullable();
            }
            //Created by
            if (!Schema::hasColumn($this->getTable(), 'created_by')) {
                $this->unsignedInteger('created_by')->nullable();
                $this->foreign('created_by')->references('id')->on(config('auth.table', 'iusers_user'))->onDelete('restrict');
            }
            //Updated by
            if (!Schema::hasColumn($this->getTable(), 'updated_by')) {
                $this->unsignedInteger('updated_by')->nullable();
                $this->foreign('updated_by')->references('id')->on(config('auth.table', 'iusers_user'))->onDelete('restrict');
            }
            //Deleted by
            if (!Schema::hasColumn($this->getTable(), 'deleted_by')) {
                $this->unsignedInteger('deleted_by')->nullable();
                $this->foreign('deleted_by')->references('id')->on(config('auth.table', 'iusers_user'))->onDelete('restrict');
            }
        });
    }

    public function register(): void
    {
        //...
    }
}
