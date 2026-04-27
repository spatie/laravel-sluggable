---
title: Upgrading from v3 to v4
weight: 11
---

The changes in v4 are mostly additive. The breaking changes are around minimum versions and a small handful of API removals.

## Minimum versions

- **PHP 8.3** or higher (was 8.2).
- **Laravel 12** or higher (Laravel 10 and 11 are no longer supported).
- **`spatie/laravel-translatable` v6** or higher when using `HasTranslatableSlug`.
- **Pest 4** and **Orchestra Testbench 10** or higher in your test suite.

Update `composer.json` accordingly and run `composer update spatie/laravel-sluggable`.

## `skipGenerateWhen()` is now evaluated per save

In v3, `skipGenerateWhen()` invoked the closure once at the moment `getSlugOptions()` ran and stored the resulting boolean on `SlugOptions::$skipGenerate`. The closure could not see model state that changed afterwards.

In v4, the closure itself is stored on `SlugOptions::$skipGenerateWhen` and called on every save. Closures that were correct in v3 keep working, but they will now re-evaluate on each persist.

The `SlugOptions::$skipGenerate` boolean property is gone. Anything reading or writing it directly needs to move to the closure form.

```php
// Both v3 and v4
return SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->skipGenerateWhen(fn () => $this->state === 'draft');
```

## `Closure` instead of `callable`

The closure-shaped options are now typed as `Closure`, not `callable`:

- `generateSlugsFrom()`
- `extraScope()`
- `usingSuffixGenerator()`
- `skipGenerateWhen()`

Array-style callables (`[$obj, 'method']`) and string callables (`'my_function'`) no longer pass the type check. Wrap them in a `fn` or a closure.

```php
// Before (v3)
->extraScope([$this, 'tenantScope'])

// After (v4)
->extraScope(fn ($query) => $this->tenantScope($query))
```

## Removed: the `$slugOptions` property on `HasSlug`

`HasSlug` no longer keeps a `protected SlugOptions $slugOptions;` cache between hooks. The trait now delegates to the `GenerateSlugAction` and reads `getSlugOptions()` directly each time it needs the options. If you overrode trait methods that read `$this->slugOptions`, switch to calling `$this->getSlugOptions()` (or override the new `generateSlugAction()` method to inject your own action).

`HasTranslatableSlug` still keeps its own `$slugOptions` field because it iterates per locale.

## Self-healing redirects are now `308` instead of `301`

The default response for a stale self-healing URL is now `308 Permanent Redirect`, replacing `301 Moved Permanently`. Both convey "permanent" semantics to search engines, but `308` preserves the request method when followed, so `PUT`/`PATCH`/`DELETE` requests to a stale URL no longer silently degrade to `GET` and return `405 Method Not Allowed`.

Update any code that asserts on the specific status code (tests, middleware, monitoring). The behavior of a custom handler registered through `SelfHealing::onStaleSelfHealingUrl()` is unchanged.

## Renamed: `Sluggable` facade is now `SelfHealing`

The facade for registering the stale-URL handler was renamed to clarify that it only customizes self-healing behavior.

```php
// Before (v3)
use Spatie\Sluggable\Facades\Sluggable;

Sluggable::onStaleSelfHealingUrl(...);

// After (v4)
use Spatie\Sluggable\Facades\SelfHealing;

SelfHealing::onStaleSelfHealingUrl(...);
```

## New (not breaking)

These additions are opt-in and require no migration:

- The `#[Sluggable]` attribute as an alternative to the `HasSlug` trait. Existing trait-based models keep working.
- Self-healing URLs via `selfHealing()` on the slug options or `selfHealing: true` on the attribute. Off by default.
- Three overridable actions (`generate_slug`, `build_self_healing_route_key`, `extract_identifier_from_self_healing_route_key`) configurable through the published `config/sluggable.php`.
- A bundled [Laravel Boost](https://github.com/laravel/boost) skill that teaches AI assistants how to use the package.
