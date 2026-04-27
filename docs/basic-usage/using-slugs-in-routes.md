---
title: Using slugs in routes
weight: 4
---

The package writes slugs to a column on your model, but it doesn't touch routing. Resolving a model from a slug in a URL is something you wire up yourself with Laravel's [implicit route model binding](https://laravel.com/docs/routing#implicit-binding). Which form you pick depends on whether the slug can change after the model is created.

## Recommended: self-healing URLs

Reach for self-healing URLs whenever the source field can be edited later, which is the typical case for posts, products, profiles, and most other user-facing content. The route key becomes `{slug}-{id}`, the primary key drives the lookup, and renaming the model `301`-redirects every old URL to the new canonical one instead of returning a `404`.

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

Bind the model to a route the usual way; the package handles the slug-and-id route key for you.

```php
// routes/web.php
Route::get('/posts/{post}', fn (Post $post) => $post);
```

See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls) for the full mechanics, the redirect behavior, and how to customize the separator.

## Plain slug binding

If the slug truly never changes after creation, for example taxonomy slugs, immutable reference data, or short-lived resources, Laravel's built-in `{parameter:column}` form is enough. Be aware that any future change to the slug column breaks every existing link, which is exactly the situation self-healing URLs are designed to avoid.

```php
// routes/web.php
Route::get('/posts/{post:slug}', fn (Post $post) => $post);
```

If every route for this model uses the slug, override `getRouteKeyName()` on the model so you can drop the explicit `:slug` from each route definition.

```php
class Post extends Model
{
    use HasSlug;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

The route parameter is now resolved against the slug column without the explicit hint.

```php
// routes/web.php
Route::get('/posts/{post}', fn (Post $post) => $post);
```
