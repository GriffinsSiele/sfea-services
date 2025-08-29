<?php
namespace App\Hashing;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Hashing\HashingManager;

class Md5HashServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('hash', function ($app) {
            return new Md5HashManager($app);
        });

        /*$this->app->singleton('hash', function ($app) {
            return new Md5Hasher($app);
        });*/

        $this->app->singleton('hash.driver', function ($app) {
            return $app['hash']->driver();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hash', 'hash.driver'];
    }
}