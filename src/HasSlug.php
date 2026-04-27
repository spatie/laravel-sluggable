<?php

namespace Spatie\Sluggable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\Exceptions\StaleSelfHealingUrl;
use Spatie\Sluggable\Support\Config;

trait HasSlug
{
    abstract public function getSlugOptions(): SlugOptions;

    protected static function bootHasSlug(): void
    {
        static::creating(fn (Model $model) => $model->generateSlugOnCreate());
        static::updating(fn (Model $model) => $model->generateSlugOnUpdate());
    }

    protected function generateSlugOnCreate(): void
    {
        $this->generateSlugAction()->onCreate($this, $this->getSlugOptions());
    }

    protected function generateSlugOnUpdate(): void
    {
        $this->generateSlugAction()->onUpdate($this, $this->getSlugOptions());
    }

    public function generateSlug(): void
    {
        $this->generateSlugAction()->generate($this, $this->getSlugOptions());
    }

    protected function generateSlugAction(): GenerateSlugAction
    {
        return Config::getAction(Config::ACTION_GENERATE_SLUG, GenerateSlugAction::class);
    }

    public function getRouteKey(): mixed
    {
        $slugOptions = $this->getSlugOptions();

        if (! $slugOptions->selfHealingUrls) {
            return parent::getRouteKey();
        }

        return Config::getAction(Config::ACTION_BUILD_SELF_HEALING_ROUTE_KEY, BuildSelfHealingRouteKeyAction::class)
            ->execute(
                $this->getSelfHealingSlugValue(),
                $this->getKey(),
                $slugOptions->selfHealingSeparator,
            );
    }

    public function resolveRouteBinding(mixed $value, $field = null): ?Model
    {
        $slugOptions = $this->getSlugOptions();

        if (! $slugOptions->selfHealingUrls) {
            return parent::resolveRouteBinding($value, $field);
        }

        $routeKey = (string) $value;
        $identifier = Config::getAction(
            Config::ACTION_EXTRACT_IDENTIFIER_FROM_SELF_HEALING_ROUTE_KEY,
            ExtractIdentifierFromSelfHealingRouteKeyAction::class,
        )->execute($routeKey, $slugOptions->selfHealingSeparator)['identifier'];

        if ($identifier === null) {
            return null;
        }

        $model = $this->newQuery()->whereKey($identifier)->first();

        if ($model === null) {
            return null;
        }

        if ($routeKey !== (string) $model->getRouteKey()) {
            throw new StaleSelfHealingUrl($model, $routeKey);
        }

        return $model;
    }

    protected function getSelfHealingSlugValue(): string
    {
        return (string) ($this->{$this->getSlugOptions()->slugField} ?? '');
    }

    public static function findBySlug(string $slug, array $columns = ['*'], ?Closure $additionalQuery = null): ?Model
    {
        $field = (new static)->getSlugOptions()->slugField;

        $query = static::query()->where($field, $slug);

        if ($additionalQuery !== null) {
            $additionalQuery($query);
        }

        return $query->first($columns);
    }
}
