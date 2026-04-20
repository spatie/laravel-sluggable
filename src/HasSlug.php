<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;
use Spatie\Sluggable\Exceptions\InvalidOption;
use Spatie\Sluggable\Exceptions\StaleSelfHealingUrl;
use Spatie\Sluggable\Support\Config;

trait HasSlug
{
    protected SlugOptions $slugOptions;

    abstract public function getSlugOptions(): SlugOptions;

    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    protected function generateSlugOnCreate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if ($this->slugOptions->skipGenerate) {
            return;
        }

        if (! $this->slugOptions->generateSlugsOnCreate) {
            return;
        }

        if ($this->slugOptions->preventOverwrite) {
            if ($this->{$this->slugOptions->slugField} !== null) {
                return;
            }
        }

        $this->addSlug();
    }

    protected function generateSlugOnUpdate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if ($this->slugOptions->skipGenerate) {
            return;
        }

        if (! $this->slugOptions->generateSlugsOnUpdate) {
            return;
        }

        if ($this->slugOptions->preventOverwrite) {
            if ($this->{$this->slugOptions->slugField} !== null) {
                return;
            }
        }

        $this->addSlug();
    }

    public function generateSlug(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->addSlug();
    }

    protected function addSlug(): void
    {
        $this->ensureValidSlugOptions();

        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlugs) {
            $slug = $this->makeSlugUnique($slug);
        }

        $slugField = $this->slugOptions->slugField;

        $this->$slugField = $slug;
    }

    protected function generateNonUniqueSlug(): string
    {
        $slugField = $this->slugOptions->slugField;

        if ($this->hasCustomSlugBeenUsed() && ! empty($this->$slugField)) {
            return $this->$slugField;
        }

        return Str::slug($this->getSlugSourceString(), $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        return $this->getOriginal($slugField) != $this->$slugField;
    }

    protected function getSlugSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = $this->getSlugSourceStringFromCallable();

            return $this->generateSubstring($slugSourceString);
        }

        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(fn (string $fieldName): string => data_get($this, $fieldName, ''))
            ->implode($this->slugOptions->slugSeparator);

        return $this->generateSubstring($slugSourceString);
    }

    protected function getSlugSourceStringFromCallable(): string
    {
        return ($this->slugOptions->generateSlugFrom)($this);
    }

    protected function makeSlugUnique(string $slug): string
    {
        $originalSlug = $slug;
        $iteration = 0;

        while (
            $slug === '' ||
            $this->otherRecordExistsWithSlug($slug) ||
            ($this->slugOptions->useSuffixOnFirstOccurrence && $iteration === 0)
        ) {
            $suffix = $this->generateSuffix($originalSlug, $iteration++);
            $slug = $originalSlug.$this->slugOptions->slugSeparator.$suffix;
        }

        return $slug;
    }

    protected function generateSuffix(string $originalSlug, int $iteration): string
    {
        if ($this->slugOptions->suffixGenerator) {
            return ($this->slugOptions->suffixGenerator)($originalSlug, $iteration);
        }

        return (string) ($this->slugOptions->startSlugSuffixFrom + $iteration);
    }

    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        $query = static::where($this->slugOptions->slugField, $slug)
            ->withoutGlobalScopes();

        if ($this->slugOptions->extraScopeCallback) {
            $query->where($this->slugOptions->extraScopeCallback);
        }

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this), true);
    }

    protected function ensureValidSlugOptions(): void
    {
        if (is_array($this->slugOptions->generateSlugFrom) && ! count($this->slugOptions->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if ($this->slugOptions->slugField === '') {
            throw InvalidOption::missingSlugField();
        }

        if ($this->slugOptions->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }

    protected function generateSubstring(string $slugSourceString): string
    {
        return mb_substr($slugSourceString, 0, $this->slugOptions->maximumLength);
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

        if (! $model) {
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
        $modelInstance = new static;
        $field = $modelInstance->getSlugOptions()->slugField;

        $query = static::query();

        if (in_array(HasTranslatableSlug::class, class_uses_recursive(static::class))) {
            $currentLocale = $modelInstance->getLocale();
            $fallbackLocale = config('app.fallback_locale');

            $currentField = "{$field}->{$currentLocale}";
            $fallbackField = "{$field}->{$fallbackLocale}";

            $query->where(fn ($query) => $query->where($currentField, $slug)->orWhere($fallbackField, $slug));
        } else {
            $query->where($field, $slug);
        }

        if (is_callable($additionalQuery)) {
            $additionalQuery($query);
        }

        return $query->first($columns);
    }
}
