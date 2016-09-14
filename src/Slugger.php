<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;

class Slugger
{
    /**
     * The Slug Options
     *
     * @var Spatie\Sluggable\SlugOptions
     */
    private $slugOptions;

    /**
     * The eloquent model that needs slugging
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    private $model;

    private function __construct(SlugOptions $slugOptions, Model $model)
    {
        $this->slugOptions = $slugOptions->guardAgainstInvalidSlugOptions();
        $this->model = $model;
    }

    /**
     * Set the slug on the passed model.
     *
     * @param SlugOptions $slugOptions
     * @param Model       $model
     */
    public static function setSlug(SlugOptions $slugOptions, Model $model)
    {
        $slugger = new self($slugOptions, $model);

        $slugger->model->{$slugger->slugOptions->slugField} = $slugger->getSlug();
    }

    private function getSlug()
    {
        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlugs) {
            return $this->makeSlugUnique($slug);
        }

        return $slug;
    }

    /**
     * Generate a non unique slug for this record.
     */
    private function generateNonUniqueSlug(): string
    {
        if ($this->hasCustomSlugBeenUsed()) {
            $slugField = $this->slugOptions->slugField;

            return $this->model->$slugField;
        }

        return str_slug($this->getSlugSourceString());
    }

    /**
     * Determine if a custom slug has been saved.
     */
    private function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        return $this->model->getOriginal($slugField) != $this->model->$slugField;
    }

    /**
     * Get the string that should be used as base for the slug.
     */
    private function getSlugSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = call_user_func($this->slugOptions->generateSlugFrom, $this->model);
            return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
        }

        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(function (string $fieldName) : string {
                return $this->model->$fieldName ?? '';
            })
            ->implode('-');

        return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Make the given slug unique.
     */
    private function makeSlugUnique(string $slug): string
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
    private function otherRecordExistsWithSlug(string $slug): bool
    {
        $query = $this->model->query()
            ->where($this->slugOptions->slugField, $slug)
            ->where($this->model->getKeyName(), '!=', $this->model->getKey() ?? '0');

        foreach ($this->slugOptions->uniqueWith as $fieldName) {
            $query->where($fieldName, $this->model->{$fieldName});
        }

        return (bool) $query->first();
    }
}
