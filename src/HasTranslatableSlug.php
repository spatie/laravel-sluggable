<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\Support\Config;

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

        if (! $this->slugOptions->generateSlugsOnCreate) {
            return;
        }

        if ($this->slugOptions->skipGenerate) {
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

        if ($this->slugOptions->skipGenerate) {
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
        $action = Config::getAction('generate_slug', GenerateSlugAction::class);

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
                    $this->slugOptions->saveSlugsTo("{$slugField}->{$locale}");

                    $slug = $action->makeUnique($slug, $this, $this->slugOptions);

                    $this->slugOptions->saveSlugsTo($slugField);
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

        return Str::slug($slugString, $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
    }

    protected function shouldPreserveExistingSlug(?string $currentSlug): bool
    {
        if (empty($currentSlug)) {
            return false;
        }

        if ($this->hasCustomSlugBeenUsed()) {
            return true;
        }

        if (is_callable($this->slugOptions->generateSlugFrom)) {
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
        return $this->buildTranslatableSourceString(
            fn (string $fieldName): string => $this->getOriginal($fieldName)[$this->getLocale()] ?? '',
        );
    }

    protected function buildTranslatableSourceString(callable $fieldReader): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $sourceString = ($this->slugOptions->generateSlugFrom)($this, $this->getLocale());
        }

        $sourceString ??= collect($this->slugOptions->generateSlugFrom)
            ->map(fn (string $fieldName): string => $fieldReader($fieldName))
            ->implode($this->slugOptions->slugSeparator);

        return mb_substr($sourceString, 0, $this->slugOptions->maximumLength);
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

    public static function findBySlug(string $slug, array $columns = ['*'], ?callable $additionalQuery = null): ?Model
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
