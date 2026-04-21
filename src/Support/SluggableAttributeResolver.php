<?php

namespace Spatie\Sluggable\Support;

use ReflectionClass;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\Exceptions\SelfHealingRequiresTrait;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SluggableAttributeResolver
{
    /** @var array<class-string, ?Sluggable> */
    protected static array $attributeCache = [];

    /** @var array<class-string, ?SlugOptions> */
    protected static array $optionsCache = [];

    public static function resolve(string $class): ?Sluggable
    {
        if (array_key_exists($class, self::$attributeCache)) {
            return self::$attributeCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Sluggable::class);

        if ($attributes === []) {
            return self::$attributeCache[$class] = null;
        }

        $sluggable = $attributes[0]->newInstance();

        self::ensureSelfHealingHasTrait($class, $sluggable);

        return self::$attributeCache[$class] = $sluggable;
    }

    public static function resolveOptions(string $class): ?SlugOptions
    {
        if (array_key_exists($class, self::$optionsCache)) {
            return self::$optionsCache[$class];
        }

        $sluggable = self::resolve($class);

        if ($sluggable === null) {
            return self::$optionsCache[$class] = null;
        }

        return self::$optionsCache[$class] = self::toSlugOptions($sluggable);
    }

    public static function toSlugOptions(Sluggable $sluggable): SlugOptions
    {
        $options = SlugOptions::create()
            ->generateSlugsFrom($sluggable->from)
            ->saveSlugsTo($sluggable->to)
            ->usingSeparator($sluggable->separator)
            ->usingLanguage($sluggable->language)
            ->slugsShouldBeNoLongerThan($sluggable->maxLength);

        if (! $sluggable->unique) {
            $options->allowDuplicateSlugs();
        }

        if (! $sluggable->onCreate) {
            $options->doNotGenerateSlugsOnCreate();
        }

        if (! $sluggable->onUpdate) {
            $options->doNotGenerateSlugsOnUpdate();
        }

        if ($sluggable->preventOverwrite) {
            $options->preventOverwrite();
        }

        if ($sluggable->selfHealing) {
            $options->selfHealing($sluggable->selfHealingSeparator);
        }

        return $options;
    }

    public static function flushCache(): void
    {
        self::$attributeCache = [];
        self::$optionsCache = [];
    }

    protected static function ensureSelfHealingHasTrait(string $class, Sluggable $sluggable): void
    {
        if (! $sluggable->selfHealing) {
            return;
        }

        if (in_array(HasSlug::class, class_uses_recursive($class), true)) {
            return;
        }

        throw SelfHealingRequiresTrait::forClass($class);
    }
}
