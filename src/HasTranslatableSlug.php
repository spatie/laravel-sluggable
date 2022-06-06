<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Localizable;

trait HasTranslatableSlug
{
    use HasSlug;
    use Localizable;

    protected function getTranslatedSlug(string $locale): string|null
    {
        $slugField = $this->slugOptions->slugField;

        return $this->getTranslations($slugField)[$locale] ?? null;
    }

    protected function getLocalesForSlug(): Collection
    {
        $generateSlugFrom = $this->slugOptions->generateSlugFrom;

        if (is_callable($generateSlugFrom)) {
            // returns a collection of locales that were given to the SlugOptions object
            // when it was instantiated with the 'createWithLocales' method.
            return Collection::make($this->slugOptions->translatableLocales);
        }

        // collects all locales for all translatable fields
        return Collection::wrap($generateSlugFrom)
            ->filter(fn ($fieldName) => $this->isTranslatableAttribute($fieldName))
            ->flatMap(fn ($fieldName) => $this->getTranslatedLocales($fieldName));
    }

    protected function addSlug(): void
    {
        $this->ensureValidSlugOptions();

        $this->getLocalesForSlug()->unique()->each(function ($locale) {
            $this->withLocale($locale, function () use ($locale) {
                $slug = $this->generateNonUniqueSlug();

                $slugField = $this->slugOptions->slugField;

                if ($this->slugOptions->generateUniqueSlugs) {
                    // temporarily change the 'slugField' of the SlugOptions
                    // so the 'otherRecordExistsWithSlug' method queries
                    // the locale JSON column instead of the 'slugField'.
                    $this->slugOptions->saveSlugsTo("{$slugField}->{$locale}");

                    $slug = $this->makeSlugUnique($slug);

                    // revert the change for the next iteration
                    $this->slugOptions->saveSlugsTo($slugField);
                }

                $this->setTranslation($slugField, $locale, $slug);
            });
        });
    }

    protected function generateNonUniqueSlug(): string
    {
        $slugString = $this->getSlugSourceString();

        $slug = $this->getTranslatedSlug($this->getLocale());

        $slugGeneratedFromCallable = is_callable($this->slugOptions->generateSlugFrom);
        $hasCustomSlug = $this->hasCustomSlugBeenUsed() && ! empty($slug);
        $hasNonChangedCustomSlug = ! $slugGeneratedFromCallable && ! empty($slug) && ! $this->slugIsBasedOnTitle();

        if ($hasCustomSlug || $hasNonChangedCustomSlug) {
            $slugString = $slug;
        }

        return $this->generateNonUniqueSlugFromString($slugString);
    }

    protected function getSlugSourceStringFromCallable(): string
    {
        return call_user_func($this->slugOptions->generateSlugFrom, $this, $this->getLocale());
    }

    protected function slugIsBasedOnTitle(): bool
    {
        $titleSlug = $this->generateNonUniqueSlugFromString($this->getOriginalSourceString());
        $currentSlug = $this->getTranslatedSlug($this->getLocale());

        if (! str_starts_with($currentSlug, $titleSlug) || $titleSlug === '') {
            return false;
        }

        if ($titleSlug === $currentSlug) {
            return true;
        }

        $slugSeparator = $currentSlug[strlen($titleSlug)];
        $slugIdentifier = substr($currentSlug, strlen($titleSlug) + 1);

        return $slugSeparator === $this->slugOptions->slugSeparator && is_numeric($slugIdentifier);
    }

    protected function getOriginalSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = $this->getSlugSourceStringFromCallable();

            return $this->generateSubstring($slugSourceString);
        }

        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(fn (string $fieldName): string => $this->getOriginal($fieldName)[$this->getLocale()] ?? '')
            ->implode($this->slugOptions->slugSeparator);

        return $this->generateSubstring($slugSourceString);
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;
        $originalSlug = $this->getOriginal($slugField)[$this->getLocale()] ?? null;
        $newSlug = $this->getTranslatedSlug($this->getLocale());

        return $originalSlug !== $newSlug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        $field = $field ?? $this->getRouteKeyName();

        if ($field !== $this->getSlugOptions()->slugField) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        return $this->queryModel($query, $value, $field);
    }

    protected function queryModel($query, $value, $field): Builder|Relation
    {
        return $query->where("{$field}->{$this->getLocale()}", $value);
    }
}
