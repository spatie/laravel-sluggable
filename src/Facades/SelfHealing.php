<?php

namespace Spatie\Sluggable\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Spatie\Sluggable\SelfHealingManager;

/**
 * @method static SelfHealingManager onStaleSelfHealingUrl(Closure $handler)
 *
 * @see SelfHealingManager
 */
class SelfHealing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SelfHealingManager::class;
    }
}
