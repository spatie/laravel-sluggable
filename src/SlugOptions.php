<?php

namespace Spatie\Sluggable;

class SlugOptions
{
    /** @var array|callable */
    public $generateSlugFrom;

    public string $slugField;

    public bool $generateUniqueSlugs = true;
    
    /** Store fields that will be unique with Slug @var array */
    public $uniqueWith = [];

    public int $maximumLength = 250;

    public bool $generateSlugsOnCreate = true;

    public bool $generateSlugsOnUpdate = true;

    public string $slugSeparator = '-';

    public string $slugLanguage = 'en';

    public static function create(): self
    {
        return new static();
    }

    /**
     * @param string|array|callable $fieldName
     *
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function generateSlugsFrom($fieldName): self
    {
        if (is_string($fieldName)) {
            $fieldName = [$fieldName];
        }

        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName): self
    {
        $this->slugField = $fieldName;

        return $this;
    }

    /**
     * @param mixed $fields
     *
     * @return \Spatie\Sluggable\SlugOptions
     */
    public function uniqueWith($fields): self
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }

        // Store fields that will be unique with slug
        $this->uniqueWith = $fields;

        return $this;
    }
    
    public function allowDuplicateSlugs(): self
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength): self
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function doNotGenerateSlugsOnCreate(): self
    {
        $this->generateSlugsOnCreate = false;

        return $this;
    }

    public function doNotGenerateSlugsOnUpdate(): self
    {
        $this->generateSlugsOnUpdate = false;

        return $this;
    }

    public function usingSeparator(string $separator): self
    {
        $this->slugSeparator = $separator;

        return $this;
    }

    public function usingLanguage(string $language): self
    {
        $this->slugLanguage = $language;

        return $this;
    }
}
