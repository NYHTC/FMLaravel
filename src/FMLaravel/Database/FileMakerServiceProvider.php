<?php namespace FMLaravel\Database;

use FMLaravel\Database\Connection;
use Illuminate\Support\ServiceProvider;

class FileMakerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function($db)
        {
            $db->extend('filemaker', function($config)
            {
                return new Connection($config);
            });
        });
    }
}