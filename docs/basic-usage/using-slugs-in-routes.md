---
title: Using slugs in routes
weight: 4
---

The package writes slugs to a column on your model, but it doesn't touch routing. Resolving a model from a slug in a URL is something you wire up yourself with Laravel's [implicit route model binding](https://laravel.com/docs/routing#implicit-binding). The simplest form points the route parameter at the slug column with `{post:slug}`.

```php
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
Route::get('/posts/{post}', fn (Post $post) => $post);
```

For slugs that should survive a title change and redirect old URLs to the canonical one, see [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).
