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
    protected static array $cache = [];

    public static function resolve(string $class): ?Sluggable
    {
        if (array_key_exists($class, self::$cache)) {
            return self::$cache[$class];
        }

        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Sluggable::class);

        if ($attributes === []) {
            return self::$cache[$class] = null;
        }

        $sluggable = $attributes[0]->newInstance();

        self::ensureSelfHealingHasTrait($class, $sluggable);

        return self::$cache[$class] = $sluggable;
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

        if ($sluggable->scope !== []) {
            $scopeFields = (array) $sluggable->scope;
            $options->extraScope(function ($query) use ($scopeFields) {
                foreach ($scopeFields as $field) {
                    $query->where($field, request()->input($field));
                }
            });
        }

        return $options;
    }

    public static function flushCache(): void
    {
        self::$cache = [];
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
