<?php

namespace Spatie\Sluggable\Actions;

class BuildSelfHealingRouteKeyAction
{
    public function execute(string $slug, int|string $identifier, string $separator): string
    {
        if ($slug === '') {
            return (string) $identifier;
        }

        return "{$slug}{$separator}{$identifier}";
    }
}
