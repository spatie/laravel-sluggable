<?php

namespace Spatie\Sluggable;

class SlugOptions
{
    /**
     * @var array
     */
    public $generateSlugFrom;

    /**
     * @var string
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

    public static function create()
    {
        return new static();
    }

    /**
     * @param string|array $fieldName
     */
    public function generateSlugFrom($fieldName) : SlugOptions
    {
        if (is_string($fieldName)) {
            $fieldName = [$fieldName];
        }

        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugTo(string $fieldName) : SlugOptions
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function duplicateSlugsAreOk() : SlugOptions
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugShouldBeNoLongerThan(int $maximumLength) : SlugOptions
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }
}
