<?php

namespace Spatie\Sluggable;

use Exception;

class InvalidOption extends Exception
{
    public static function missingFromField(Array $invalidFields = [])
    {
        if ( ! empty($invalidFields)) {
            $invalidFields = collect($invalidFields)->implode(', ');

            return new static("Fields {$invalidFields} are not valid sources for sluggification");
        }

        return new static('Could not determine which fields should be sluggified');
    }

    public static function missingSlugField()
    {
        return new static('Could not determine in which field the slug should be saved');
    }

    public static function invalidMaximumLength()
    {
        return new static('Maximum length should be greater than zero');
    }
}
