<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\Exceptions\StaleSelfHealingUrl;
use Spatie\Sluggable\Support\Config;

trait HasSlug
{
    protected SlugOptions $slugOptions;

    abstract public function getSlugOptions(): SlugOptions;

    protected static function bootHasSlug(): void
    {
        static::creating(fn (Model $model) => $model->generateSlugOnCreate());
        static::updating(fn (Model $model) => $model->generateSlugOnUpdate());
    }

    protected function generateSlugOnCreate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        Config::getAction('generate_slug', GenerateSlugAction::class)
            ->onCreate($this, $this->slugOptions);
    }

    protected function generateSlugOnUpdate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        Config::getAction('generate_slug', GenerateSlugAction::class)
            ->onUpdate($this, $this->slugOptions);
    }

    public function generateSlug(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        Config::getAction('generate_slug', GenerateSlugAction::class)
            ->generate($this, $this->slugOptions);
    }

    public function getRouteKey(): mixed
    {
        $slugOptions = $this->getSlugOptions();

        if (! $slugOptions->selfHealingUrls) {
            return parent::getRouteKey();
        }

        $action = Config::getAction('build_self_healing_route_key', BuildSelfHealingRouteKeyAction::class);

        return $action->execute(
            $this->getSelfHealingSlugValue(),
            $this->getKey(),
            $slugOptions->selfHealingSeparator,
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $slugOptions = $this->getSlugOptions();

        if (! $slugOptions->selfHealingUrls) {
            return parent::resolveRouteBinding($value, $field);
        }

        $action = Config::getAction(
            'extract_identifier_from_self_healing_route_key',
            ExtractIdentifierFromSelfHealingRouteKeyAction::class,
        );

        $routeKey = (string) $value;
        $identifier = $action->execute($routeKey, $slugOptions->selfHealingSeparator)['identifier'];

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

    public static function findBySlug(string $slug, array $columns = ['*'], ?callable $additionalQuery = null): ?Model
    {
        $field = (new static)->getSlugOptions()->slugField;

        $query = static::query()->where($field, $slug);

        if ($additionalQuery !== null) {
            $additionalQuery($query);
        }

        return $query->first($columns);
    }
}
