---
title: Overriding the underlying actions
weight: 6
---

The package delegates low-level work to three action classes that can each be swapped for your own.

| Action key | Default class | Purpose |
| --- | --- | --- |
| `generate_slug` | `Spatie\Sluggable\Actions\GenerateSlugAction` | Generates the slug on create and update. |
| `build_self_healing_route_key` | `Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction` | Composes the `{slug}{separator}{id}` route key. |
| `extract_identifier_from_self_healing_route_key` | `Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction` | Splits an incoming route value back into `slug` and `identifier`. |

## Publishing the config

```bash
php artisan vendor:publish --tag=sluggable-config
```

Edit the resulting `config/sluggable.php` and point the relevant key at your own class. Replacement classes must extend the default action so the package can type-check the override.

```php
// config/sluggable.php
return [
    'actions' => [
        'generate_slug' => App\Sluggable\MyGenerateSlugAction::class,
        'build_self_healing_route_key' => Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction::class,
        'extract_identifier_from_self_healing_route_key' => Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction::class,
    ],
];
```

## Example: uppercase slugs in the route key

```php
namespace App\Sluggable;

use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;

class UppercaseRouteKeyAction extends BuildSelfHealingRouteKeyAction
{
    public function execute(string $slug, int|string $identifier, string $separator): string
    {
        return parent::execute(strtoupper($slug), $identifier, $separator);
    }
}
```

```php
// config/sluggable.php
'build_self_healing_route_key' => App\Sluggable\UppercaseRouteKeyAction::class,
```

## How resolution works

Action classes are resolved through `Spatie\Sluggable\Support\Config`, which reads the `sluggable.actions.{key}` entry, instantiates the class via the container, and validates that it extends the expected type. Any class resolved this way can use constructor injection just like a regular service.
