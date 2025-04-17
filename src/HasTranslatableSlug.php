<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

trait HasTranslatableSlug
{
    use HasSlug;
    use Localizable;

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
                    // temporarly change the 'slugField' of the SlugOptions
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
        $slugField = $this->slugOptions->slugField;
        $slugString = $this->getSlugSourceString();

        $slug = $this->getTranslations($slugField)[$this->getLocale()] ?? null;

        $slugGeneratedFromCallable = is_callable($this->slugOptions->generateSlugFrom);
        $hasCustomSlug = $this->hasCustomSlugBeenUsed() && ! empty($slug);
        $hasNonChangedCustomSlug = ! $slugGeneratedFromCallable && ! empty($slug) && ! $this->slugIsBasedOnTitle();

        if ($hasCustomSlug || $hasNonChangedCustomSlug) {
            $slugString = $slug;
        }

        return Str::slug($slugString, $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
    }

    protected function getSlugSourceStringFromCallable(): string
    {
        return call_user_func($this->slugOptions->generateSlugFrom, $this, $this->getLocale());
    }

    protected function slugIsBasedOnTitle(): bool
    {
        $slugField = $this->slugOptions->slugField;
        $titleSlug = Str::slug($this->getOriginalSourceString(), $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
        $currentSlug = $this->getTranslations($slugField)[$this->getLocale()] ?? null;

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
        $newSlug = $this->getTranslations($slugField)[$this->getLocale()] ?? null;

        return $originalSlug !== $newSlug;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        $field = $field ?? $this->getRouteKeyName();
        $slug = $this->getSlugOptions()->slugField;

        if (str_contains($field, '.') && str_ends_with($field, ".{$slug}")) {
            return $query->where("{$field}->{$this->getLocale()}", $value);
        }

        if ($field === $slug) {
            return $query->where("{$field}->{$this->getLocale()}", $value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
