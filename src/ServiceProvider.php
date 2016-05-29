<?php

namespace Spatie\Sluggable;

use Illuminate\Support\Composer;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sluggable.migrate', function ($app) {
            return new SlugThemAll(
                $app[MigrationCreator::class],
                $app['migrator'],
                $app['composer']
            );
        });

        $this->commands('sluggable.migrate');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sluggable.migrate'];
    }
}
