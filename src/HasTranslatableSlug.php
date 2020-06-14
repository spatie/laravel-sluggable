<?php

namespace Spatie\Sluggable;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Localizable;

trait HasTranslatableSlug
{
    use Localizable;
    use HasSlug {
         addSlug as originalAddSlug;
    }

    protected function getLocalesForSlug(): Collection
    {
        $generateSlugFrom = $this->slugOptions->generateSlugFrom;

        if (is_callable($generateSlugFrom)) {
            return Collection::make($this->slugOptions->translatableLocales);
        }

        return Collection::wrap($generateSlugFrom)
            ->flatMap(fn ($fieldName) => $this->getTranslatedLocales($fieldName))
            ->unique();
    }

    protected function addSlug()
    {
        $this->ensureValidSlugOptions();

        $this->getLocalesForSlug()->each(function ($locale) {
            $this->withLocale($locale, function () use ($locale) {
                $slug = $this->generateNonUniqueSlug();

                $slugField = $this->slugOptions->slugField;

                if ($this->slugOptions->generateUniqueSlugs) {
                    $this->slugOptions->saveSlugsTo("{$slugField}->{$locale}");

                    $slug = $this->makeSlugUnique($slug);

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
        $slugField = $this->slugOptions->slugField;

        return $this->getOriginal($slugField) != $this->getTranslation($slugField, $this->getLocale(), false);
    }
}
