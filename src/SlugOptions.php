<?php

namespace Spatie\Sluggable;

class SlugOptions
{
    /** @var string|array|callable */
    public $generateSlugFrom;

    /** @var string */
    public $slugField;

    /** @var bool */
    public $generateUniqueSlugs = true;

    /** @var int */
    public $maximumLength = 250;

    /** @var bool */
    public $includeSoftDeletes = false;

    public static function create(): SlugOptions
    {
        return new static();
    }

    /**
     * @param string|array|callable $fieldName
     *
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function generateSlugsFrom($fieldName): SlugOptions
    {
        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName): SlugOptions
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs(): SlugOptions
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength): SlugOptions
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function checkAgainstTrashedModels() : SlugOptions
    {
        $this->includeSoftDeletes = true;

        return $this;
    }
}
