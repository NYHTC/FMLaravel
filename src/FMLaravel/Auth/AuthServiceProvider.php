<?php namespace FMLaravel\Auth;

use Illuminate\Support\ServiceProvider;
use \Auth;
use FMLaravel\Auth\FileMakerUserProvider;
use FMLaravel\Auth\Auth as FMAuth;

class AuthServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Auth::extend('filemaker', function ($app) {
        
            return new FileMakerUserProvider(new FMAuth($app));
        });
    }

    public function register()
    {
        $this->app->bindShared('fmauth', function ($app) {
        
            return new FMAuth($app);
        });
    }
}
