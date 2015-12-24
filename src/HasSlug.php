<?php

namespace Spatie\Sluggable;

trait HasSlug
{
    /**
     * @var SlugOptions
     */
    protected $slugOptions;

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Sluggable $model) {
            $model->addSlug();
        });

        static::updating(function (Sluggable $model) {
            $model->addSlug();
        });
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        $this->slugOptions = $this->getSlugOptions();

        $slug = str_slug($this->getSlugSourceString());

        if ($this->slugOptions->generateUniqueSlugs) {
            $slug = $this->makeSlugUnique($slug);
        }

        $slugField = $this->slugOptions->slugField;

        $this->$slugField = $slug;
    }

    /**
     * Get the string that should be used as base for the slug.
     */
    protected function getSlugSourceString() : string
    {
        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(function (string $fieldName) : string
{
    return $this->$fieldName;
})
            ->implode('-');

        return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Make the given slug unique.
     */
    protected function makeSlugUnique(string $slug) : string
    {
        $originalSlug = $slug;
        $i = 1;

        while ($this->recordExistsWithSlug($slug)) {
            $slug = $originalSlug.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Determine if a record exists with the given slug.
     */
    protected function recordExistsWithSlug(string $slug) : bool
    {
        return (bool) static::where($this->slugOptions->slugField, $slug)->first();
    }
}
