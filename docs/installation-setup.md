---
title: Installation & setup
weight: 4
---

Install the package via composer:

```bash
composer require spatie/laravel-sluggable
```

The service provider is registered automatically via Laravel package discovery.

## Publishing the config file (optional)

The package ships with a config file that lists overridable actions. Publishing it is only required if you want to swap one of the default actions for your own class.

```bash
php artisan vendor:publish --tag=sluggable-config
```

The published file will look like this:

```php
return [
    'actions' => [
        'generate_slug' => Spatie\Sluggable\Actions\GenerateSlugAction::class,
        'build_self_healing_route_key' => Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction::class,
        'extract_identifier_from_self_healing_route_key' => Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction::class,
    ],
];
```

See [Overriding actions](/docs/laravel-sluggable/v4/advanced-usage/overriding-actions) for details.
