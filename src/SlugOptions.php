<?php

namespace Spatie\Sluggable;

class SlugOptions
{
    /**
     * @var array
     */
    public $generateSlugFrom;

    /**
     * @var string|array
     */
    public $slugField;

    /**
     * @var bool
     */
    public $generateUniqueSlugs = true;

    /**
     * @var int
     */
    public $maximumLength = 250;

    public static function create() : SlugOptions
    {
        return new static();
    }

    /**
     * @param string|array $fieldName
     */
    public function generateSlugsFrom($fieldName) : SlugOptions
    {
        if (is_string($fieldName)) {
            $fieldName = [$fieldName];
        }

        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName) : SlugOptions
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs() : SlugOptions
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength) : SlugOptions
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }
}
