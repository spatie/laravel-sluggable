<?php

namespace Spatie\Sluggable;

use Spatie\Sluggable\InvalidOption;

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

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     */
    public function guardAgainstInvalidSlugOptions()
    {
        if (!count($this->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if (!strlen($this->slugField)) {
            throw InvalidOption::missingSlugField();
        }

        if ($this->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }

        return $this;
    }
}
