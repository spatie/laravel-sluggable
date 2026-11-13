# Upgrading

## From v5 to v6

Version 6 is in development. Do not upgrade a production application until a v6 release is available.

The requirements remain PHP 8.4+, Laravel 13, and `spatie/laravel-translatable` 6 when translating slugs. The package's tests use Pest 5 and Testbench 11; these are not application runtime dependencies. No database migration or slug regeneration is required.

### UUID and ULID models use combined traits

`HasSlug` now implements `resolveRouteBindingQuery()` so scoped bindings and query-based consumers accept self-healing keys automatically. Laravel's `HasUuids` and `HasUlids` define the same method, so replace the two separate traits with the matching combined trait, even if self-healing is disabled:

| Before | After |
| --- | --- |
| `HasSlug` + Laravel's `HasUuids` | `Spatie\Sluggable\HasSlugWithUuids` |
| `HasSlug` + Laravel's `HasUlids` | `Spatie\Sluggable\HasSlugWithUlids` |
| `HasTranslatableSlug` + Laravel's `HasUuids` | `Spatie\Sluggable\HasTranslatableSlugWithUuids` |
| `HasTranslatableSlug` + Laravel's `HasUlids` | `Spatie\Sluggable\HasTranslatableSlugWithUlids` |

```php
// Before
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Sluggable\HasSlug;

class Post extends Model
{
    use HasSlug;
    use HasUuids;
}

// After
use Spatie\Sluggable\HasSlugWithUuids;

class Post extends Model
{
    use HasSlugWithUuids;
}
```

Keep your existing `getSlugOptions()` method or `#[Sluggable]` attribute. Translatable models must also keep `HasTranslations`. Custom `newUniqueId()` implementations still take precedence over the combined trait's default.

### Remove the query-binding opt-in

Remove a `resolveRouteBindingQuery()` method whose only purpose is to call `resolveSelfHealingRouteBindingQuery()`. The package now does that automatically. Keep custom binding behavior you still need, but delegate to the package query hook rather than starting a new query that discards the parent relationship.

Query lookups accept stale keys without redirecting and retain the supplied relationship constraints, global scopes and soft-delete filtering. Ordinary implicit bindings still redirect stale keys with `308`.

Non-scoped `{post:id}` and `{post:slug}` routes retain literal column lookups. In scoped bindings, Laravel supplies the route key name for both implicit and explicitly named bindings. When that field matches the model's route key name, the query hook accepts composite self-healing keys as well as ordinary identifiers.

### Keep your URL separator

The route-key format has not changed. UUID models must continue using a separator that does not occur inside the identifier, such as `selfHealing(separator: '--')`, or their own build/extract actions. Changing the separator changes existing URLs; the combined traits do not change it for you.

### Custom query-binding implementations

`resolveRouteBindingQuery()` now has a `Builder|Relation` return type. Subclasses overriding it must use a compatible return type. For translated slug lookups, `HasTranslatableSlug` now customizes the protected `resolveSlugRouteBindingQuery()` hook. The protected `resolveModelRouteBindingQuery()` hook delegates ordinary identifier validation and lookup to Laravel, including in the combined UUID/ULID traits.

## From v4 to v5

Version 5 requires PHP 8.4 and Laravel 13. The package's development stack requires Pest 5 and Testbench 11. Version 4 supported PHP 8.3 and Laravel 12 as well.

Named-column bindings such as `{post:id}` and `{post:slug}` now look up that column literally, including routes using `withTrashed()`. Remove workarounds that parsed those values as composite self-healing keys. Default `{post}` bindings keep their self-healing behavior.

## From v3 to v4

```bash
composer require spatie/laravel-sluggable "^4.0.0"
```

The changes in v4 are mostly additive. The breaking changes are around minimum versions and a small handful of API removals.

### Minimum versions

- **PHP 8.3** or higher (was 8.2).
- **Laravel 12** or higher (Laravel 10 and 11 are no longer supported).
- **`spatie/laravel-translatable` v6** or higher when using `HasTranslatableSlug`.
- **Pest 4** and **Orchestra Testbench 10** or higher for the package's test suite.

Update `composer.json` accordingly and run `composer update spatie/laravel-sluggable`.

### `skipGenerateWhen()` is now evaluated per save

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

### `Closure` instead of `callable`

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

### Removed: the `$slugOptions` property on `HasSlug`

`HasSlug` no longer keeps a `protected SlugOptions $slugOptions;` cache between hooks. The trait now delegates to the `GenerateSlugAction` and reads `getSlugOptions()` directly each time it needs the options. If you overrode trait methods that read `$this->slugOptions`, switch to calling `$this->getSlugOptions()` (or override the new `generateSlugAction()` method to inject your own action).

`HasTranslatableSlug` still keeps its own `$slugOptions` field because it iterates per locale.

### Self-healing redirects are now `308` instead of `301`

The default response for a stale self-healing URL is now `308 Permanent Redirect`, replacing `301 Moved Permanently`. Both convey "permanent" semantics to search engines, but `308` preserves the request method when followed, so `PUT`/`PATCH`/`DELETE` requests to a stale URL no longer silently degrade to `GET` and return `405 Method Not Allowed`.

Update any code that asserts on the specific status code (tests, middleware, monitoring). The behavior of a custom handler registered through `SelfHealing::onStaleSelfHealingUrl()` is unchanged.

### Renamed: `Sluggable` facade is now `SelfHealing`

The facade for registering the stale-URL handler was renamed to clarify that it only customizes self-healing behavior.

```php
// Before (v3)
use Spatie\Sluggable\Facades\Sluggable;

Sluggable::onStaleSelfHealingUrl(...);

// After (v4)
use Spatie\Sluggable\Facades\SelfHealing;

SelfHealing::onStaleSelfHealingUrl(...);
```

### New (not breaking)

These additions are opt-in and require no migration:

- The `#[Sluggable]` attribute as an alternative to the `HasSlug` trait. Existing trait-based models keep working.
- Self-healing URLs via `selfHealing()` on the slug options or `selfHealing: true` on the attribute. Off by default.
- Three overridable actions (`generate_slug`, `build_self_healing_route_key`, `extract_identifier_from_self_healing_route_key`) configurable through the published `config/sluggable.php`.
- A bundled [Laravel Boost](https://github.com/laravel/boost) skill that teaches AI assistants how to use the package.
