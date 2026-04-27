---
title: Overriding the underlying actions
weight: 3
---

The package delegates low-level work to three action classes that can each be swapped for your own.

- **`generate_slug`** generates the slug on create and update.  
  Default: `Spatie\Sluggable\Actions\GenerateSlugAction`.
- **`build_self_healing_route_key`** composes the `{slug}{separator}{id}` route key.  
  Default: `Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction`.
- **`extract_identifier_from_self_healing_route_key`** splits an incoming route value back into `slug` and `identifier`.  
  Default: `Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction`.

## Publishing the config

Publish the configuration file before pointing any of the action keys at a custom class.

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

Extend the default action and override its single `execute()` method to wrap the call.

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

Then point the config key at the new class.

```php
// config/sluggable.php
'build_self_healing_route_key' => App\Sluggable\UppercaseRouteKeyAction::class,
```
