---
title: Using slugs in routes
weight: 3
---

To resolve a model from its slug in a route, use Laravel's [implicit route model binding](https://laravel.com/docs/routing#implicit-binding). The simplest approach points the route parameter at the slug column.

```php
Route::get('/posts/{post:slug}', fn (Post $post) => $post);
```

If every route for this model uses the slug, override `getRouteKeyName()` on the model so you can drop the explicit `:slug`:

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

```php
Route::get('/posts/{post}', fn (Post $post) => $post);
```

For slugs that should survive a title change and redirect old URLs to the canonical one, see [Self-healing URLs](/docs/laravel-sluggable/v4/self-healing-urls/overview).
