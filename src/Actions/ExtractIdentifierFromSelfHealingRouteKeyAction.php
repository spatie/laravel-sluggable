<?php

namespace Spatie\Sluggable\Actions;

class ExtractIdentifierFromSelfHealingRouteKeyAction
{
    /**
     * @return array{
     *     slug: string,
     *     identifier: ?string,
     * }
     */
    public function execute(string $value, string $separator): array
    {
        $position = strrpos($value, $separator);

        if ($position === false) {
            return ['slug' => $value, 'identifier' => null];
        }

        $identifier = substr($value, $position + strlen($separator));

        if ($identifier === '') {
            return ['slug' => $value, 'identifier' => null];
        }

        return [
            'slug' => substr($value, 0, $position),
            'identifier' => $identifier,
        ];
    }
}
