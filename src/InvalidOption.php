<?php

namespace Edofre\Sluggable;

use Exception;

/**
 * Class InvalidOption
 * @package Edofre\Sluggable
 */
class InvalidOption extends Exception
{
    /**
     * @return static
     */
    public static function missingFromField()
    {
        return new static('Could not determinate which fields should be sluggified');
    }

    /**
     * @return static
     */
    public static function missingSlugField()
    {
        return new static('Could not determinate in which field the slug should be saved');
    }

    /**
     * @return static
     */
    public static function invalidMaximumLength()
    {
        return new static('Maximum length should be greater than zero');
    }
}
