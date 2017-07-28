<?php
/**
 * AuthServiceProvider Provider.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Auth\JwtGuard;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
    }

    /**
     * Boot the authentication services for the application.
     */
    public function boot()
    {
        //Register our custom driver with our custom Guard
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard($app['auth']->createUserProvider($config['provider']), $app['request']);
        });
    }
}
