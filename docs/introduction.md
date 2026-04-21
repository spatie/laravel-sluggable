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

## Self-healing URLs at a glance

Enable self-healing on the slug options, and the model's route key becomes `{slug}-{id}`. Old slugs stay resolvable and redirect to the canonical URL.

```php
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->selfHealing();
    }
}
```

```php
$post = Post::create(['title' => 'Hello World']);
$post->getRouteKey(); // "hello-world-5"
```

```php
Route::get('/posts/{post}', fn (Post $post) => $post);
```

| Request | Response |
| --- | --- |
| `GET /posts/hello-world-5` | `200 OK` |
| `GET /posts/outdated-slug-5` | `301` redirect to `/posts/hello-world-5` |
| `GET /posts/hello-world-99` | `404` when id `99` does not exist |

Read more in [Self-healing URLs](/docs/laravel-sluggable/v4/self-healing-urls/overview).
