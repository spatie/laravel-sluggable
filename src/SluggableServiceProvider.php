<?php

namespace Spatie\Sluggable;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\Support\Config;
use Spatie\Sluggable\Support\SluggableAttributeResolver;
use Spatie\Sluggable\Support\TraitDetector;

class SluggableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sluggable.php', 'sluggable');

        $this->app->singleton(SelfHealingManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sluggable.php' => config_path('sluggable.php'),
            ], 'sluggable-config');
        }

        $this->registerAttributeListeners();
    }

    protected function registerAttributeListeners(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make('events');

        $events->listen('eloquent.creating: *', $this->handleAttributeEvent('onCreate'));
        $events->listen('eloquent.updating: *', $this->handleAttributeEvent('onUpdate'));
    }

    protected function handleAttributeEvent(string $method): Closure
    {
        return function (string $event, array $payload) use ($method): void {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
                return;
            }

            if (TraitDetector::uses($model::class, HasSlug::class)) {
                return;
            }

            $options = SluggableAttributeResolver::resolveOptions($model::class);

            if ($options === null) {
                return;
            }

            Config::getAction(Config::ACTION_GENERATE_SLUG, GenerateSlugAction::class)
                ->{$method}($model, $options);
        };
    }
}
