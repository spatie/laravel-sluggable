---
title: Self-healing URLs
weight: 5
---

Say you publish a blog post titled "Hello World". Its URL is `/posts/hello-world`. A few days later you realise the title should have been "Hello Universe", so you update it. The slug regenerates to `hello-universe` and the URL becomes `/posts/hello-universe`. Every search engine result, every shared link, every bookmark pointing at `/posts/hello-world` now returns `404`.

Self-healing URLs fix this. The route key becomes `{slug}-{id}`. The slug part can change without breaking lookups, because the primary key still resolves the model. Stale slugs trigger a `308` redirect to the canonical URL.

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
| `/posts/outdated-slug-5` | `308 Permanent Redirect` to `/posts/hello-world-5`. |
| `/posts/hello-world-99` | `404 Not Found` when id `99` does not exist. |
| `/posts/hello-world` | `404 Not Found`, no identifier in the URL. |

A route that names its column, such as `/admin/posts/{post:id}` or `/posts/{post:slug}`, looks that column up as usual. Only `{post}` uses the self-healing route key.

The redirect uses `308` rather than the more familiar `301` because `308` preserves the request method when followed. A stale `PUT`, `PATCH`, or `DELETE` to a self-healing URL still arrives at the canonical route as the same verb. With `301`, clients are allowed to rewrite the method to `GET`, which would turn the request into a `405 Method Not Allowed` on resource routes. Search engines treat both statuses the same for ranking, so SEO is unaffected. To return something else, see [Customizing the redirect](#customizing-the-redirect) below.

## Scoped bindings and query-based lookups

`HasSlug` and `HasTranslatableSlug` automatically accept self-healing keys through `resolveRouteBindingQuery()`. No model-level binding method is needed. This includes Laravel's scoped child bindings:

```php
Route::get('authors/{author}/posts/{post}', fn (Author $author, Post $post) => $post)
    ->scopeBindings();
```

The lookup adds the extracted primary key to Laravel's existing relationship query. A post belonging to another author still returns `404`. Existing query constraints, global scopes, and soft-delete filtering remain in place.

Query-based lookups accept both canonical and stale keys without redirecting, as well as ordinary identifiers. They interpret the value when the field is omitted or matches `getRouteKeyName()`, including its qualified column name. Other columns keep their ordinary lookup behavior. Laravel passes the same field for a default scoped binding and an explicitly named route-key column, so a scoped `{post:id}` binding also accepts composite keys when `id` is the route key name. Ordinary implicit self-healing bindings still redirect stale keys with `308`; non-scoped named-column bindings look up the literal column value.

## UUID and ULID models

Use the combined trait for your model's identifier type. Do not combine `HasSlug` directly with Laravel's `HasUuids` or `HasUlids`: both define the route query method and PHP rejects that combination.

| Model | Trait |
| --- | --- |
| UUID | `Spatie\Sluggable\HasSlugWithUuids` |
| ULID | `Spatie\Sluggable\HasSlugWithUlids` |
| Translatable UUID | `Spatie\Sluggable\HasTranslatableSlugWithUuids` |
| Translatable ULID | `Spatie\Sluggable\HasTranslatableSlugWithUlids` |

Each combined trait includes slug generation and Laravel's identifier generation. The translatable variants still need `Spatie\Translatable\HasTranslations`. These traits also work when self-healing is disabled.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlugWithUuids;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlugWithUuids;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->selfHealing(separator: '--');
    }
}
```

For UUID primary keys, use a separator that does not occur inside the UUID, such as `--`. The default parser splits at the last separator; `-` would split inside a UUID. Keep the same separator when upgrading so existing URLs remain valid.

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

Or via the trait:

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->selfHealing(separator: '--');

// route key: "hello-world--5"
```

## Customizing the redirect

When an incoming URL's slug is stale, the package throws a `Spatie\Sluggable\Exceptions\StaleSelfHealingUrl` exception. Its `render()` method delegates to the `SelfHealingManager`, which by default returns a `308` redirect to the canonical URL.

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
            return redirect()->route('posts.show', $model, status: 307);
        });
    }
}
```

Use cases include:

- Returning a `307` temporary redirect instead of the permanent `308`.
- Rendering an "old link" notification before redirecting.
- Logging the stale access for analytics.
- Refusing to redirect based on request state.

## Changing the URL format

The default route key looks like `{slug}-{id}`. To use a different layout, for example `{id}-{slug}`, swap the two actions that build and parse the route key. They are configurable on `config/sluggable.php` and the package ships a worked example for the id-first format. See [Putting the identifier first](../advanced-usage/overriding-actions#example-putting-the-identifier-first) on the overriding actions page.

## Under the hood

### A fresh request

When someone visits `/posts/hello-world-5`, the package splits the URL at the last separator. The right side is the primary key, so it goes looking for the post with id `5`. It finds the post, confirms that its current slug is still `hello-world`, and hands the model to your controller. Nothing special happens. The request is served normally.

### A stale link

Now imagine you rename the post to "Hello Universe". The slug in the database becomes `hello-universe`, but the old link `/posts/hello-world-5` is still floating around on Twitter, in Google's index, and in somebody's bookmarks.

When that old link hits your app, the package again pulls `5` off the end and loads the post. This time the slug in the URL does not match the post's current slug, so the package sends back a `308` redirect to `/posts/hello-universe-5`. The visitor (or the search engine crawler) follows the redirect and lands on the canonical URL.

### No database writes

The database is never touched by this process. The package only reads. Your slug column is updated the usual way, through Eloquent, when you save the model. Visiting a stale URL doesn't regenerate a slug, doesn't store the old one anywhere, and doesn't leave any trace.

Because the lookup is always by primary key, the slug column doesn't need to be unique, and changing a title never orphans an existing link.

## When you don't need self-healing

If your slug truly never changes after creation (taxonomy slugs, immutable reference data, short-lived resources), Laravel's built-in [implicit route model binding](https://laravel.com/docs/routing#implicit-binding) is enough. Point the route parameter at the slug column with `{post:slug}`, or override `getRouteKeyName()` on the model to return `'slug'` and drop the explicit hint. Be aware that any future change to the slug column breaks every existing link, which is exactly the situation self-healing exists to prevent.

```php
// routes/web.php
Route::get('/posts/{post:slug}', fn (Post $post) => $post);
```
