<?php

namespace Shamaseen\Repository;

use Illuminate\Support\ServiceProvider;
use Shamaseen\Repository\Commands\Generator;
use Shamaseen\Repository\Commands\Remover;

/**
 * Class GeneratorServiceProvider.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Generator::class,
                Remover::class
            ]);
        }

        //configs
        $this->publishes([
            __DIR__.'/config/repository.php' => config_path('repository.php'),
            __DIR__.'/lang/repository.php' => lang_path('en/repository.php'),
        ], 'repository');


        //if the configuration is not published then use the package one
        if (null === $this->app['config']->get('repository')) {
            $this->app['config']->set('repository', require __DIR__.'/config/repository.php');
        }

        //return either the published config or the package one
        $config = $this->app['config']->get('repository');

        //stubs
        $this->publishes([
            realpath(__DIR__.'/stubs') => $config['stubs_path'],
            __DIR__.'/lang' => $config['lang_path'].'/en',
        ], 'repository-stubs');
    }

    /**
     * Register services.
     */
    public function register()
    {
    }
}
