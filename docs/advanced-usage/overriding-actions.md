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

## Example: putting the identifier first

The default route key looks like `hello-world-5`. To flip it to `5-hello-world` instead, override both self-healing actions: one to build the new format, one to parse it back into a slug and an identifier.

The build action moves the identifier in front of the slug.

```php
namespace App\Sluggable;

use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;

class IdFirstBuildAction extends BuildSelfHealingRouteKeyAction
{
    public function execute(string $slug, int|string $identifier, string $separator): string
    {
        if ($slug === '') {
            return (string) $identifier;
        }

        return "{$identifier}{$separator}{$slug}";
    }
}
```

The extractor reads the identifier from the front of the value instead of the end. The default uses `strrpos` (last separator) because slugs can contain the separator. The id-first version uses `strpos` (first separator), which is safe as long as the identifier never contains the separator. Numeric ids and ULIDs do not.

```php
namespace App\Sluggable;

use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;

class IdFirstExtractAction extends ExtractIdentifierFromSelfHealingRouteKeyAction
{
    public function execute(string $value, string $separator): array
    {
        $position = strpos($value, $separator);

        if ($position === false) {
            return ['slug' => $value, 'identifier' => null];
        }

        $identifier = substr($value, 0, $position);

        if ($identifier === '' || ! ctype_digit($identifier)) {
            return ['slug' => $value, 'identifier' => null];
        }

        return [
            'slug' => substr($value, $position + strlen($separator)),
            'identifier' => $identifier,
        ];
    }
}
```

The `ctype_digit` check rejects values that have no numeric prefix. Drop it (or replace it with a regex that matches your key format) when models use ULID, UUID, or other non-numeric primary keys.

Wire both classes into the config.

```php
// config/sluggable.php
'build_self_healing_route_key' => App\Sluggable\IdFirstBuildAction::class,
'extract_identifier_from_self_healing_route_key' => App\Sluggable\IdFirstExtractAction::class,
```

A `Post` with id `5` and slug `hello-world` now exposes `5-hello-world` as its route key, and a stale URL like `/posts/5-old-title` still resolves the post and `308`-redirects to `/posts/5-hello-world`.
