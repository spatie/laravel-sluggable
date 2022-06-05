<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Spatie\Sluggable\Models\Slug;

trait HasShareableTranslatableSlug
{
    use HasTranslatableSlug;

    protected Collection $overrideSlugs;

    protected function initializeHasShareableTranslatableSlug()
    {
        $this->overrideSlugs = Collection::empty();
    }

    protected static function bootHasSlug(): void
    {
        static::created(function (Model $model) {
            $model->createOverrideSlugs();
            $model->generateSlugOnCreate();
            $model->clearOverrideSlugs();
        });

        static::updated(function (Model $model) {
            $model->createOverrideSlugs();
            $model->generateSlugOnUpdate();
            $model->clearOverrideSlugs();
        });

        static::deleted(function (Model $model) {
            $model->clearSlugs();
        });
    }

    protected function setSlugField(string $value): void
    {
        $this->setOverrideSlug($value);
        unset($this->slug);
    }

    protected function setOverrideSlug(string $slug): void
    {
        $this->overrideSlugs->put($this->getLocale(), $slug);
    }

    protected function clearSlugs(): void
    {
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return;
        }

        $this->slugs()->delete();
    }

    protected function isSlugEmpty(): bool
    {
        if ($this->exists) {
            return $this->slugs()->exists();
        }

        return $this->overrideSlugs->isNotEmpty();
    }

    protected function createOverrideSlugs(): void
    {
        $this->overrideSlugs->each(function (string $name, string $locale) {
            $this->slugs()->create([
                'name' => $name,
                'locale' => $locale
            ]);
        });
    }

    protected function clearOverrideSlugs(): void
    {
        $this->overrideSlugs = Collection::empty();
    }

    protected function addSlug(): void
    {
        $this->ensureValidSlugOptions();

        $this->deleteForgottenSlugs();

        $this->getModifiedLocalesForSlug()->unique()->each(function ($locale) {

            $this->withLocale($locale, function () use ($locale) {

                $slug = $this->generateNonUniqueSlug();

                if ($this->slugOptions->generateUniqueSlugs) {
                    $slug = $this->makeSlugUnique($slug);
                }

                $this->updateOrCreateSlug($slug);
            });
        });
    }

    protected function deleteForgottenSlugs()
    {
        $locales = $this->getLocalesForSlug();

        $this->slugs()
            ->whereNotIn('locale', $locales)
            ->each(function (Slug $slug) {
                $slug->delete();
            });
    }

    protected function getModifiedLocalesForSlug(): Collection
    {
        $generateSlugFrom = $this->slugOptions->generateSlugFrom;

        if (is_callable($generateSlugFrom)) {
            // returns a collection of locales that were given to the SlugOptions object
            // when it was instantiated with the 'createWithLocales' method.
            return Collection::make($this->slugOptions->translatableLocales);
        }

        return Collection::wrap($generateSlugFrom)
            ->filter(fn ($fieldName) => $this->isTranslatableAttribute($fieldName))
            ->flatMap(function (string $fieldName) {
                return Collection::wrap($this->getTranslations($fieldName))
                    ->diff($this->getOriginal($fieldName))
                    ->keys();
            });
    }

    protected function updateOrCreateSlug(string $name): void
    {
        $this->slugs()->updateOrCreate(
            ['locale' => $this->getLocale()],
            ['name' => $name]
        );
    }

    protected function getTranslatedSlug(string $locale): string|null
    {
        return $this->slugs()->where('locale', $locale)->first()?->name;
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        return false;
    }

    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function getSlug(string $locale = null): string|null
    {
        if ($this->overrideSlugs->contains($locale)) {
            return $this->overrideSlugs->get($locale);
        }

        return $this->slugs()
            ->where('locale', $locale ?? $this->getLocale())
            ->first()?->name;
    }

    public function setSlug(string $value, string $locale = null)
    {
        $this->slugOptions = $this->getSlugOptions();
        $slug = $this->generateNonUniqueSlugFromString($value);

        $locale = $locale ?? $this->getLocale();

        $this->withLocale($locale, function () use ($slug) {

            if ($this->slugOptions->generateUniqueSlugs) {
                $slug = $this->makeSlugUnique($slug);
            }

            if (!$this->exists) {
                $this->setOverrideSlug($slug);
                return;
            }

            $this->updateOrCreateSlug($slug);
        });
    }

    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        $query = Slug::where('name', $slug)
            ->where('sluggable_type', static::class)
            ->whereNot('sluggable_id', $this->getKey());

        if ($this->slugOptions->extraScopeCallback) {
            $query->where($this->slugOptions->extraScopeCallback);
        }

        return $query->exists();
    }

    protected function queryModel($query, $value, $field): Builder|Relation
    {
        return $query->whereHas('slugs', function ($query) use ($value) {
            $query->where('name', $value);
        });
    }

}
