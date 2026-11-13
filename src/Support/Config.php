<?php

namespace Spatie\Sluggable\Support;

use Spatie\Sluggable\Exceptions\InvalidConfig;

class Config
{
    public const ACTION_GENERATE_SLUG = 'generate_slug';

    public const ACTION_BUILD_SELF_HEALING_ROUTE_KEY = 'build_self_healing_route_key';

    public const ACTION_EXTRACT_IDENTIFIER_FROM_SELF_HEALING_ROUTE_KEY = 'extract_identifier_from_self_healing_route_key';

    /**
     * @template T of object
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
     * @template T of object
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
