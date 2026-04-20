<?php

namespace Spatie\Sluggable\Exceptions;

use Exception;
use Spatie\Sluggable\HasSlug;

class SelfHealingRequiresTrait extends Exception
{
    public static function forClass(string $class): self
    {
        $trait = HasSlug::class;

        return new self(
            "Model `{$class}` enables `selfHealing` via the `#[Sluggable]` attribute but does not use the `{$trait}` trait. Self-healing URLs require overriding `getRouteKey()` and `resolveRouteBinding()`, which the attribute alone cannot do. Add `use HasSlug;` to the model.",
        );
    }
}
