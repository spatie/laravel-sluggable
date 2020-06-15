<?php

namespace Spatie\Sluggable;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Localizable;

trait HasTranslatableSlug
{
    use HasSlug, Localizable;

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

    protected function addSlug()
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

    protected function getSlugSourceStringFromCallable(): string
    {
        return call_user_func($this->slugOptions->generateSlugFrom, $this, $this->getLocale());
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        return false;
    }
}
