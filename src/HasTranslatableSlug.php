<?php

namespace Spatie\Sluggable;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

trait HasTranslatableSlug
{
    use HasSlug;
    use Localizable;

    protected SlugOptions $slugOptions;

    protected function getLocalesForSlug(): Collection
    {
        $generateSlugFrom = $this->slugOptions->generateSlugFrom;

        if ($generateSlugFrom instanceof Closure) {
            return Collection::make($this->slugOptions->translatableLocales);
        }

        return Collection::wrap($generateSlugFrom)
            ->filter($this->isTranslatableAttribute(...))
            ->flatMap($this->getTranslatedLocales(...));
    }

    protected function generateSlugOnCreate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugsOnCreate) {
            return;
        }

        if ($this->shouldSkipGeneration()) {
            return;
        }

        $this->addSlug();
    }

    protected function generateSlugOnUpdate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugsOnUpdate) {
            return;
        }

        if ($this->shouldSkipGeneration()) {
            return;
        }

        $this->addSlug();
    }

    protected function shouldSkipGeneration(): bool
    {
        return $this->generateSlugAction()->shouldSkipBasedOnSkipWhen($this->slugOptions);
    }

    public function generateSlug(): void
    {
        $this->slugOptions = clone $this->getSlugOptions();
        $this->slugOptions->preventOverwrite = false;

        $this->addSlug();
    }

    protected function addSlug(): void
    {
        $action = $this->generateSlugAction();

        $action->ensureValidOptions($this->slugOptions);

        $slugField = $this->slugOptions->slugField;

        $this->getLocalesForSlug()->unique()->each(function ($locale) use ($slugField, $action) {
            if ($this->slugOptions->preventOverwrite) {
                if (filled($this->getTranslation($slugField, $locale, false))) {
                    return;
                }
            }

            $this->withLocale($locale, function () use ($slugField, $locale, $action) {
                $slug = $this->generateNonUniqueSlug();

                if ($this->slugOptions->generateUniqueSlugs) {
                    $localeOptions = clone $this->slugOptions;
                    $localeOptions->slugField = "{$slugField}->{$locale}";

                    $slug = $action->makeUnique($slug, $this, $localeOptions);
                }

                $this->setTranslation($slugField, $locale, $slug);
            });
        });
    }

    protected function generateNonUniqueSlug(): string
    {
        $slugField = $this->slugOptions->slugField;
        $currentSlug = $this->getTranslations($slugField)[$this->getLocale()] ?? null;

        $slugString = $this->shouldPreserveExistingSlug($currentSlug)
            ? $currentSlug
            : $this->getSlugSourceString();

        return $this->generateSlugAction()->slugifySource($slugString, $this->slugOptions);
    }

    /**
     * The translatable trait keeps its own slug-preservation logic (not GenerateSlugAction's
     * `hasCustomSlugBeenUsed`) because translation reads go through `getTranslations()`, not
     * a plain attribute, and because the "slug derives from current title" check is locale-aware.
     * Unifying the two would require teaching the action about JSON columns and locales.
     */
    protected function shouldPreserveExistingSlug(?string $currentSlug): bool
    {
        if (blank($currentSlug)) {
            return false;
        }

        if ($this->hasCustomSlugBeenUsed()) {
            return true;
        }

        if ($this->slugOptions->generateSlugFrom instanceof Closure) {
            return false;
        }

        return ! $this->slugIsBasedOnTitle();
    }

    protected function getSlugSourceString(): string
    {
        return $this->buildTranslatableSourceString(
            fn (string $fieldName): string => data_get($this, $fieldName, ''),
        );
    }

    protected function slugIsBasedOnTitle(): bool
    {
        $slugField = $this->slugOptions->slugField;
        $titleSlug = Str::slug($this->getOriginalSourceString(), $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
        $currentSlug = (string) ($this->getTranslations($slugField)[$this->getLocale()] ?? '');

        if ($titleSlug === '' || $currentSlug === '') {
            return false;
        }

        if ($titleSlug === $currentSlug) {
            return true;
        }

        $separator = preg_quote($this->slugOptions->slugSeparator, '/');
        $title = preg_quote($titleSlug, '/');

        return preg_match("/^{$title}{$separator}\d+$/", $currentSlug) === 1;
    }

    protected function getOriginalSourceString(): string
    {
        return $this->buildTranslatableSourceString(
            fn (string $fieldName): string => $this->getOriginal($fieldName)[$this->getLocale()] ?? '',
        );
    }

    protected function buildTranslatableSourceString(Closure $fieldReader): string
    {
        return $this->generateSlugAction()->buildSourceString(
            $this->slugOptions,
            fn (string $fieldName): string => (string) $fieldReader($fieldName),
            fn (Closure $source): string => (string) $source($this, $this->getLocale()),
        );
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;
        $originalSlug = $this->getOriginal($slugField)[$this->getLocale()] ?? null;
        $newSlug = $this->getTranslations($slugField)[$this->getLocale()] ?? null;

        return $originalSlug !== $newSlug;
    }

    protected function getSelfHealingSlugValue(): string
    {
        $slugField = $this->getSlugOptions()->slugField;

        return (string) ($this->getTranslation($slugField, $this->getLocale(), false) ?? '');
    }

    public function getLocalizedRouteKey(string $locale): mixed
    {
        $originalLocale = $this->getLocale();

        try {
            $this->setLocale($locale);

            return $this->getRouteKey();
        } finally {
            $this->setLocale($originalLocale);
        }
    }

    public static function findBySlug(string $slug, array $columns = ['*'], ?Closure $additionalQuery = null): ?Model
    {
        $modelInstance = new static;
        $field = $modelInstance->getSlugOptions()->slugField;

        $currentField = "{$field}->{$modelInstance->getLocale()}";
        $fallbackField = "{$field}->".config('app.fallback_locale');

        $query = static::query()
            ->where(fn ($query) => $query->where($currentField, $slug)->orWhere($fallbackField, $slug));

        if ($additionalQuery !== null) {
            $additionalQuery($query);
        }

        return $query->first($columns);
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        if ($this->getSlugOptions()->selfHealingUrls) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        $field ??= $this->getRouteKeyName();
        $slug = $this->getSlugOptions()->slugField;

        if ($field === $slug || str_ends_with($field, ".{$slug}")) {
            return $query->where("{$field}->{$this->getLocale()}", $value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
