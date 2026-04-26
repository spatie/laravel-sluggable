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
    /** @var array<class-string, bool> */
    protected static array $usesHasSlugCache = [];

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
            $options = $this->resolveAttributeOptions($payload);

            if ($options === null) {
                return;
            }

            Config::getAction('generate_slug', GenerateSlugAction::class)
                ->onCreate($payload[0], $options);
        });

        $events->listen('eloquent.updating: *', function (string $event, array $payload): void {
            $options = $this->resolveAttributeOptions($payload);

            if ($options === null) {
                return;
            }

            Config::getAction('generate_slug', GenerateSlugAction::class)
                ->onUpdate($payload[0], $options);
        });
    }

    /**
     * @param  array<int, mixed>  $payload
     */
    protected function resolveAttributeOptions(array $payload): ?SlugOptions
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model) {
            return null;
        }

        if ($this->modelUsesHasSlug($model::class)) {
            return null;
        }

        return SluggableAttributeResolver::resolveOptions($model::class);
    }

    protected function modelUsesHasSlug(string $class): bool
    {
        return self::$usesHasSlugCache[$class]
            ??= in_array(HasSlug::class, class_uses_recursive($class), true);
    }
}
