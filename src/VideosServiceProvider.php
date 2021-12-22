<?php

namespace Admin\ResourceTools\Videos;

use Illuminate\Support\ServiceProvider;
use InWeb\Admin\App\Admin;
use InWeb\Admin\App\AdminRoute;
use InWeb\Admin\App\Events\ServingAdmin;

class VideosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Admin::serving(function (ServingAdmin $event) {
            Admin::script('videos-tool', __DIR__.'/../dist/js/resource-tool.js');
            Admin::style('videos-tool', __DIR__.'/../dist/css/resource-tool.css');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        AdminRoute::api('\Admin\ResourceTools\Videos', function () {
            \Route::group(['prefix' => 'resource-tool/videos'], function () {
                $this->registerRoutes();
            });
        });
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
