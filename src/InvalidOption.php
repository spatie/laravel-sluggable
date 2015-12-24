<?php

namespace Spatie\Sluggable;

use Exception;

class InvalidOption extends Exception
{
    public static function missingFromField()
    {
        return new static('could not determinate which fields should be sluggified');
    }

    public static function missingSlugField()
    {
        return new static('could not determinate in which field to save the slug');
    }

    public static function invalidMaximumLength()
    {
        return new static('maximum lenght should be greater than zero');
    }
}
