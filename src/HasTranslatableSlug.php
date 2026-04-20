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
            return Collection::make($this->slugOptions->translatableLocales);
        }

        return Collection::wrap($generateSlugFrom)
            ->filter(fn ($fieldName) => $this->isTranslatableAttribute($fieldName))
            ->flatMap(fn ($fieldName) => $this->getTranslatedLocales($fieldName));
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

        $this->addSlug();
    }

    public function generateSlug(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->slugOptions->preventOverwrite = false;

        $this->addSlug();
    }

    protected function addSlug(): void
    {
        $this->ensureValidSlugOptions();

        $slugField = $this->slugOptions->slugField;

        $this->getLocalesForSlug()->unique()->each(function ($locale) use ($slugField) {
            if ($this->slugOptions->preventOverwrite) {
                if (filled($this->getTranslation($slugField, $locale, false))) {
                    return;
                }
            }

            $this->withLocale($locale, function () use ($slugField, $locale) {
                $slug = $this->generateNonUniqueSlug();

                if ($this->slugOptions->generateUniqueSlugs) {
                    $this->slugOptions->saveSlugsTo("{$slugField}->{$locale}");

                    $slug = $this->makeSlugUnique($slug);

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
        return ($this->slugOptions->generateSlugFrom)($this, $this->getLocale());
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

    protected function getSelfHealingSlugValue(): string
    {
        $slugField = $this->getSlugOptions()->slugField;

        return (string) ($this->getTranslation($slugField, $this->getLocale(), false) ?? '');
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        if ($this->getSlugOptions()->selfHealingUrls) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        $field = $field ?? $this->getRouteKeyName();
        $slug = $this->getSlugOptions()->slugField;

        if (str_contains($field, '.')) {
            if (str_ends_with($field, ".{$slug}")) {
                return $query->where("{$field}->{$this->getLocale()}", $value);
            }
        }

        if ($field === $slug) {
            return $query->where("{$field}->{$this->getLocale()}", $value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
