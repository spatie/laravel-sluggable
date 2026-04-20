<?php

use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;

return [
    /*
     * The actions that perform low-level operations of this package.
     *
     * You can extend the default actions and specify your own actions
     * here to customize the package's behavior.
     */
    'actions' => [
        'build_self_healing_route_key' => BuildSelfHealingRouteKeyAction::class,
        'extract_identifier_from_self_healing_route_key' => ExtractIdentifierFromSelfHealingRouteKeyAction::class,
    ],
];
