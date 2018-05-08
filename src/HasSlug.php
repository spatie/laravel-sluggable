<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    /** @var \Spatie\Sluggable\SlugOptions */
    protected $slugOptions;

    /**
     * Get the options for generating the slug.
     */
    abstract public function getSlugOptions(): SlugOptions;

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    /**
     * Handle adding slug on model creation.
     */
    protected function generateSlugOnCreate()
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugsOnCreate) {
            return;
        }

        $this->addSlug();
    }

    /**
     * Handle adding slug on model update.
     */
    protected function generateSlugOnUpdate()
    {
        $this->slugOptions = $this->getSlugOptions();

        if (! $this->slugOptions->generateSlugsOnUpdate) {
            return;
        }

        $this->addSlug();
    }

    /**
     * Handle setting slug on explicit request.
     */
    public function generateSlug()
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->addSlug();
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        $this->guardAgainstInvalidSlugOptions();

        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlugs) {
            $slug = $this->makeSlugUnique($slug);
        }

        $slugField = $this->slugOptions->slugField;

        $this->$slugField = $slug;
    }

    /**
     * Generate a non unique slug for this record.
     */
    protected function generateNonUniqueSlug(): string
    {
        if ($this->hasCustomSlugBeenUsed()) {
            $slugField = $this->slugOptions->slugField;

            return $this->$slugField;
        }

        return str_slug($this->getSlugSourceString(), $this->slugOptions->slugSeparator);
    }

    /**
     * Determine if a custom slug has been saved.
     */
    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        return $this->getOriginal($slugField) != $this->$slugField;
    }

    /**
     * Get the string that should be used as base for the slug.
     */
    protected function getSlugSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = call_user_func($this->slugOptions->generateSlugFrom, $this);

            return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
        }

        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(function (string $fieldName) : string {
                return $this->$fieldName ?? '';
            })
            ->implode($this->slugOptions->slugSeparator);

        return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Make the given slug unique.
     */
    protected function makeSlugUnique(string $slug): string
    {
        $originalSlug = $slug;
        $i = 1;

        while ($this->otherRecordExistsWithSlug($slug) || $slug === '') {
            $slug = $originalSlug.$this->slugOptions->slugSeparator.$i++;
        }

        return $slug;
    }

    /**
     * Determine if a record exists with the given slug.
     */
    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        return (bool) static::where($this->slugOptions->slugField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?? '0')
            ->withoutGlobalScopes()
            ->first();
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     */
    protected function guardAgainstInvalidSlugOptions()
    {
        if (! $this->slugOptions->generateSlugFrom) {
            throw InvalidOption::missingFromField();
        }

        if (! strlen($this->slugOptions->slugField)) {
            throw InvalidOption::missingSlugField();
        }

        if ($this->slugOptions->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }
}
