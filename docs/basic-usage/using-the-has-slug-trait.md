---
title: Using the HasSlug trait
weight: 3
---

The `HasSlug` trait is the long form of the same configuration the `#[Sluggable]` attribute encodes. Add the trait, implement `getSlugOptions()`, and return a `SlugOptions` instance. With nothing else added the model behaves identically to one annotated `#[Sluggable(from: 'title', to: 'slug')]`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

Reach for the trait when you need any of the following. None can be expressed through static attribute arguments.

## A closure as the source field

Compute the slug from arbitrary model state, including related models, by passing a closure to `generateSlugsFrom()`.

```php
return SlugOptions::create()
    ->generateSlugsFrom(fn (Post $post) => "{$post->author->name} {$post->title}")
    ->saveSlugsTo('slug');
```

## Skip slug generation conditionally

`skipGenerateWhen()` accepts a closure that runs on every save. Returning `true` leaves the slug column untouched for that save.

```php
return SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->skipGenerateWhen(fn () => $this->state === 'draft');
```

## Scope uniqueness to a subset of rows

`extraScope()` narrows the uniqueness check, so two records can share a slug as long as they differ on the scope columns.

```php
return SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->extraScope(fn ($query) => $query->where('tenant_id', $this->tenant_id));
```

## A custom suffix generator

Replace the default `-1`, `-2`, ... collision suffix with your own. The closure receives the base slug and the collision iteration.

```php
return SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->usingSuffixGenerator(fn (string $slug, int $iteration) => bin2hex(random_bytes(4)));
```

## Find a model by its slug

The trait adds a static `findBySlug()` helper so you don't have to hand-write the `where()`.

```php
$post = Post::findBySlug('hello-world');
```

See [Finding models by slug](/docs/laravel-sluggable/v4/basic-usage/finding-models-by-slug) for the full signature.

## Regenerate a slug on demand

The trait exposes a public `generateSlug()` method that forces regeneration outside the normal save lifecycle. Call `save()` afterwards to persist the new value.

```php
$post->generateSlug();
$post->save();
```

## Translatable slugs

`HasTranslatableSlug` (which uses `HasSlug` under the hood) generates one slug per locale. See [Translatable slugs](/docs/laravel-sluggable/v4/translatable-slugs).

## Self-healing URLs

Self-healing requires the trait so it can override `getRouteKey()` and `resolveRouteBinding()`. The feature itself can be enabled through the attribute (`selfHealing: true`) or through the slug options (`->selfHealing()`). See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).
