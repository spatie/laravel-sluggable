---
title: Self-healing URLs
weight: 5
---

A self-healing URL pairs the slug with the model's primary key, so the slug portion can change freely without breaking existing links. Route model binding resolves the model by the primary key, and stale slugs trigger a `301` redirect to the canonical URL.

## Enabling

Self-healing requires the `HasSlug` trait, because the feature overrides `getRouteKey()` and `resolveRouteBinding()`. Setting `selfHealing: true` on the attribute without the trait throws a `SelfHealingRequiresTrait` exception.

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('title')
        ->saveSlugsTo('slug')
        ->selfHealing();
}
```

With default options, a `Post` with id `5` and title "Hello World" exposes a route key of `hello-world-5`:

```php
$post = Post::create(['title' => 'Hello World']);
$post->getRouteKey(); // "hello-world-5"
```

## Request behavior

```php
Route::get('/posts/{post}', fn (Post $post) => $post);
```

| Incoming path | Result |
| --- | --- |
| `/posts/hello-world-5` | `200 OK` with the resolved model. |
| `/posts/outdated-slug-5` | `301 Moved Permanently` to `/posts/hello-world-5`. |
| `/posts/hello-world-99` | `404 Not Found` when id `99` does not exist. |
| `/posts/hello-world` | `404 Not Found`, no identifier in the URL. |

## Translatable slugs

`HasTranslatableSlug` supports self-healing as well. The route key uses the slug for the current locale.

```php
$post->setLocale('en');
$post->getRouteKey(); // "english-title-5"

$post->setLocale('nl');
$post->getRouteKey(); // "nederlandse-titel-5"
```

## Choosing a separator

The default separator is `-`. If your slugs can legitimately end with a number followed by a hyphen, use a separator that cannot collide with slug values.

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->selfHealing(separator: '--');

// route key: "hello-world--5"
```

## Customizing the redirect

When an incoming URL's slug is stale, the package throws a `Spatie\Sluggable\Exceptions\StaleSelfHealingUrl` exception. Its `render()` method delegates to the `SelfHealingManager`, which by default returns a `301` redirect to the canonical URL.

Register a closure through the `Sluggable` facade in a service provider's `boot()` method. The closure receives the resolved model, the stale route key, and the incoming request, and returns whatever response you want.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Sluggable\Facades\Sluggable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sluggable::onStaleSelfHealingUrl(function (Model $model, string $staleRouteKey, Request $request) {
            return redirect()->route('posts.show', $model, status: 302);
        });
    }
}
```

Use cases include:

- Returning a `302` redirect instead of `301`.
- Rendering an "old link" notification before redirecting.
- Logging the stale access for analytics.
- Refusing to redirect based on request state.

## Under the hood

The `HasSlug` trait overrides two methods from Eloquent's route model binding to make self-healing work.

`getRouteKey()` returns `{slug}{separator}{id}` when `selfHealing` is enabled on the slug options. That is the string used by `route()`, `URL::route()`, and implicit model binding when building URLs.

`resolveRouteBinding()` handles the other side of the trip. When Laravel resolves a `{post}` parameter, the trait:

1. Splits the incoming value at the rightmost occurrence of the separator. The right side is treated as the primary key, the left side as the slug.
2. Loads the model by its primary key (`whereKey($identifier)`). The slug portion is not used for the lookup, so changing a title never orphans an existing URL.
3. Compares the incoming route key with the model's canonical `getRouteKey()`. If they match, the model is returned as normal. If they differ, a `Spatie\Sluggable\Exceptions\StaleSelfHealingUrl` exception is thrown.

The exception carries the resolved model and the stale route key. Its `render()` method hands off to the `SelfHealingManager`, which either invokes the closure registered via `Sluggable::onStaleSelfHealingUrl(...)` or falls back to a `301` redirect to the canonical URL.

A few consequences follow from this design:

- The primary key must appear at the end of the route key, which is why the default separator is `-` and why a custom separator is needed when slugs can end in `-{number}`.
- Lookups are always by primary key, so self-healing URLs do not depend on the slug column being unique.
- Because `resolveRouteBinding()` is on the model, the behavior is scoped to models that use the trait. Other models in the same application are unaffected.
