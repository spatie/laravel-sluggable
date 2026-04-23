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

Self-healing works by hooking into Laravel's route model binding in two places.

| Phase | What happens |
| --- | --- |
| Building a URL | The primary key is appended to the slug with the separator in between. That combined value is the canonical URL. |
| Incoming request | The primary key is pulled off the end of the URL, and the model is loaded by that key. The slug portion is not used for the lookup. |
| Canonical match | If the URL matches the model's current slug, the request continues as normal. |
| Canonical mismatch | If the URL contains an outdated slug, a stale URL exception fires. By default the package returns a `301` to the canonical URL. A custom closure can replace that behavior. |

Two consequences follow:

- Because the model is looked up by primary key, the slug column does not need to be unique. Changing a title never orphans an existing URL.
- The primary key lives at the end of the URL, so the separator has to be a string that cannot appear at the end of a slug. That is why custom separators matter when slugs can end in a number.
