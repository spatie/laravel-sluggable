---
title: Introduction
weight: 1
---

This package generates a unique slug for any Eloquent model whenever it is created or updated. Pick whichever configuration style fits the model: place the `#[Sluggable]` attribute on the class for simple cases, or add the `HasSlug` trait and a `getSlugOptions()` method for full control.

```php
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
}

$post = Post::create(['title' => 'activerecord is awesome']);
$post->slug; // "activerecord-is-awesome"
```

Slugs are produced by Laravel's `Str::slug` helper, which lowercases and replaces spaces with a configurable separator.

On top of generation, this package also ships:

- **Self-healing URLs**: route keys that combine the slug with the primary key so changing a slug never breaks existing links.
- **Translatable slugs**: integration with `spatie/laravel-translatable` to store one slug per locale.
- **Overridable actions**: swap the slug generator or the self-healing URL logic for your own class via a config file.
- **Laravel Boost skill**: a bundled [Boost](https://github.com/laravel/boost) skill that teaches AI assistants how to use this package. See [Laravel Boost skill](/docs/laravel-sluggable/v4/laravel-boost-skill).

## Self-healing URLs at a glance

Bookmarks, search results, and shared links keep working when a slug changes. Enable `selfHealing` on the attribute (or the slug options) and the route key becomes `{slug}-{id}`. The primary key resolves the model, the slug just rides along, and stale URLs `301`-redirect to the canonical one.

Read the full story in [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).
