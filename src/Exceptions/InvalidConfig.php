<?php

namespace Spatie\Sluggable\Exceptions;

use Exception;

class InvalidConfig extends Exception
{
    public static function actionKeyNotFound(string $actionKey): static
    {
        return new static("No action class is configured for key `{$actionKey}`.");
    }

    public static function actionClassDoesNotExist(string $actionClass): static
    {
        return new static("Action class `{$actionClass}` does not exist.");
    }

    public static function actionClassDoesNotExtend(string $actionClass, string $mustBeOrExtend): static
    {
        return new static("Action class `{$actionClass}` must be or extend `{$mustBeOrExtend}`.");
    }
}
