<?php

namespace Spatie\Sluggable\Support;

class TraitDetector
{
    /** @var array<class-string, array<class-string, bool>> */
    protected static array $cache = [];

    /**
     * @param  class-string  $trait
     */
    public static function uses(object|string $target, string $trait): bool
    {
        $class = is_object($target) ? $target::class : $target;

        return self::$cache[$class][$trait]
            ??= in_array($trait, class_uses_recursive($class), true);
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
