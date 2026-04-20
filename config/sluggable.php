<?php

return [
    /*
     * The actions that perform low-level operations of this package.
     *
     * You can extend the default actions and specify your own actions
     * here to customize the package's behavior.
     */
    'actions' => [
        'build_self_healing_route_key' => Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction::class,
        'extract_identifier_from_self_healing_route_key' => Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction::class,
    ],
];
