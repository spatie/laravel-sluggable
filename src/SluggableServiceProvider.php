<?php

namespace Spatie\Sluggable;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\Support\Config;
use Spatie\Sluggable\Support\SluggableAttributeResolver;

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

        $events->listen('eloquent.creating: *', function (string $event, array $payload): void {
            $this->dispatchIfAttributed($payload, 'onCreate');
        });

        $events->listen('eloquent.updating: *', function (string $event, array $payload): void {
            $this->dispatchIfAttributed($payload, 'onUpdate');
        });
    }

    /**
     * @param  array<int, mixed>  $payload
     */
    protected function dispatchIfAttributed(array $payload, string $hook): void
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model) {
            return;
        }

        if (in_array(HasSlug::class, class_uses_recursive($model), true)) {
            return;
        }

        $sluggable = SluggableAttributeResolver::resolve($model::class);

        if ($sluggable === null) {
            return;
        }

        $options = SluggableAttributeResolver::toSlugOptions($sluggable);

        Config::getAction('generate_slug', GenerateSlugAction::class)->$hook($model, $options);
    }
}
