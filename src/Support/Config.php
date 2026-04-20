<?php

namespace Spatie\Sluggable\Support;

use Spatie\Sluggable\Exceptions\InvalidConfig;

class Config
{
    /**
     * @template T
     *
     * @param  class-string<T>  $mustBeOrExtend
     * @return class-string<T>
     */
    public static function getActionClass(string $actionKey, string $mustBeOrExtend): string
    {
        $actionClass = config("sluggable.actions.{$actionKey}");

        if (! $actionClass) {
            throw InvalidConfig::actionKeyNotFound($actionKey);
        }

        if (! class_exists($actionClass)) {
            throw InvalidConfig::actionClassDoesNotExist($actionClass);
        }

        if (! is_a($actionClass, $mustBeOrExtend, true)) {
            throw InvalidConfig::actionClassDoesNotExtend($actionClass, $mustBeOrExtend);
        }

        return $actionClass;
    }

    /**
     * @template T
     *
     * @param  class-string<T>  $mustBeOrExtend
     * @return T
     */
    public static function getAction(string $actionKey, string $mustBeOrExtend): object
    {
        $actionClass = self::getActionClass($actionKey, $mustBeOrExtend);

        return app($actionClass);
    }
}
