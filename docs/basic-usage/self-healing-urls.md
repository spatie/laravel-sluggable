---
title: Self-healing URLs
weight: 5
---

Say you publish a blog post titled "Hello World". Its URL is `/posts/hello-world`. A few days later you realise the title should have been "Hello Universe", so you update it. The slug regenerates to `hello-universe` and the URL becomes `/posts/hello-universe`. Every search engine result, every shared link, every bookmark pointing at `/posts/hello-world` now returns `404`.

Self-healing URLs fix this. The route key becomes `{slug}-{id}`. The slug part can change without breaking lookups, because the primary key still resolves the model. Stale slugs trigger a `301` redirect to the canonical URL.

## Enabling

Self-healing requires the `HasSlug` trait, because the feature overrides `getRouteKey()` and `resolveRouteBinding()`. Setting `selfHealing: true` on the attribute without the trait throws a `SelfHealingRequiresTrait` exception.

```php
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\HasSlug;

#[Sluggable(
    from: 'title',
    to: 'slug',
    selfHealing: true,
)]
class Post extends Model
{
    use HasSlug;
}
```

Or with the trait alone:

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

Bind the model to a route the usual way and the package handles the slug-and-id route key for you.

```php
// routes/web.php
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
#[Sluggable(
    from: 'title',
    to: 'slug',
    selfHealing: true,
    selfHealingSeparator: '--',
)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->selfHealing(separator: '--');

// route key: "hello-world--5"
```

## Customizing the redirect

When an incoming URL's slug is stale, the package throws a `Spatie\Sluggable\Exceptions\StaleSelfHealingUrl` exception. Its `render()` method delegates to the `SelfHealingManager`, which by default returns a `301` redirect to the canonical URL.

Register a closure through the `SelfHealing` facade in a service provider's `boot()` method. The closure receives the resolved model, the stale route key, and the incoming request, and returns whatever response you want.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Sluggable\Facades\SelfHealing;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        SelfHealing::onStaleSelfHealingUrl(function (Model $model, string $staleRouteKey, Request $request) {
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

### A fresh request

When someone visits `/posts/hello-world-5`, the package splits the URL at the last separator. The right side is the primary key, so it goes looking for the post with id `5`. It finds the post, confirms that its current slug is still `hello-world`, and hands the model to your controller. Nothing special happens. The request is served normally.

### A stale link

Now imagine you rename the post to "Hello Universe". The slug in the database becomes `hello-universe`, but the old link `/posts/hello-world-5` is still floating around on Twitter, in Google's index, and in somebody's bookmarks.

When that old link hits your app, the package again pulls `5` off the end and loads the post. This time the slug in the URL does not match the post's current slug, so the package sends back a `301` redirect to `/posts/hello-universe-5`. The visitor (or the search engine crawler) follows the redirect and lands on the canonical URL.

### No database writes

The database is never touched by this process. The package only reads. Your slug column is updated the usual way, through Eloquent, when you save the model. Visiting a stale URL doesn't regenerate a slug, doesn't store the old one anywhere, and doesn't leave any trace.

Because the lookup is always by primary key, the slug column doesn't need to be unique, and changing a title never orphans an existing link.

## When you don't need self-healing

If your slug truly never changes after creation (taxonomy slugs, immutable reference data, short-lived resources), Laravel's built-in [implicit route model binding](https://laravel.com/docs/routing#implicit-binding) is enough. Point the route parameter at the slug column with `{post:slug}`, or override `getRouteKeyName()` on the model to return `'slug'` and drop the explicit hint. Be aware that any future change to the slug column breaks every existing link, which is exactly the situation self-healing exists to prevent.

```php
// routes/web.php
Route::get('/posts/{post:slug}', fn (Post $post) => $post);
```
