<?php

namespace Spatie\Sluggable\Exceptions;

use Exception;

class InvalidConfig extends Exception
{
    public static function actionKeyNotFound(string $actionKey): self
    {
        return new self("No action class is configured for key `{$actionKey}`.");
    }

    public static function actionClassDoesNotExist(string $actionClass): self
    {
        return new self("Action class `{$actionClass}` does not exist.");
    }

    public static function actionClassDoesNotExtend(string $actionClass, string $mustBeOrExtend): self
    {
        return new self("Action class `{$actionClass}` must be or extend `{$mustBeOrExtend}`.");
    }
}
