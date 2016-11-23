<?php

namespace Idmkr\Platformify\Providers;

use Illuminate\Support\ServiceProvider;
use Schema;
use Session;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;


class CodeceptionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        if ( $this->app->runningInConsole() ) {
            $this->app['Illuminate\Contracts\Http\Kernel']->deleteMiddleware('Platform\Installer\Middleware\Installer');
            $this->app['Illuminate\Contracts\Http\Kernel']->deleteMiddleware('App\Http\Middleware\VerifyCsrfToken');
            if(Schema::hasTable('extensions')) {
                $this->app['platform']->setupExtensions();
                $this->app['platform']->bootExtensions();
            }
            //Make Cartalyst Flash Alerts Last Longer
            $events->listen('kernel.handled', function ($request, $response) {
                    Session::put('cartalyst.alerts_old', Session::get('cartalyst.alerts'));
            });

        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }
}
