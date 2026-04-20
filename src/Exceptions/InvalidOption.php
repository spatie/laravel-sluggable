<?php

namespace Spatie\Sluggable\Exceptions;

use Exception;

class InvalidOption extends Exception
{
    public static function missingFromField(): self
    {
        return new self('Could not determine which fields should be sluggified');
    }

    public static function missingSlugField(): self
    {
        return new self('Could not determine in which field the slug should be saved');
    }

    public static function invalidMaximumLength(): self
    {
        return new self('Maximum length should be greater than zero');
    }
}
