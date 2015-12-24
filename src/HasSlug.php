<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    /**
     * @var \Spatie\Sluggable\SlugOptions
     */
    protected $slugOptions;

    /**
     * Get the options for generating the slug.
     */
    abstract public function getSlugOptions() : SlugOptions;

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->addSlug();
        });

        static::updating(function (Model $model) {
            $model->addSlug();
        });
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->guardAgainstInvalidSlugOptions();

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
            ->map(function (string $fieldName) : string {
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

        while ($this->otherRecordExistsWithSlug($slug) || $slug === '') {
            $slug = $originalSlug.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Determine if a record exists with the given slug.
     */
    protected function otherRecordExistsWithSlug(string $slug) : bool
    {
        return (bool) static::where($this->slugOptions->slugField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?? '')
            ->first();
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     */
    protected function guardAgainstInvalidSlugOptions()
    {
        if (!count($this->slugOptions->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if (!strlen($this->slugOptions->slugField)) {
            throw InvalidOption::missingSlugField();
        }

        if ($this->slugOptions->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }
}
