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

Say you publish a blog post titled "Hello World". Its URL is `/posts/hello-world`. A few days later you realise the title should have been "Hello Universe", so you update it. The slug regenerates to `hello-universe` and the URL becomes `/posts/hello-universe`. Every search engine result, every shared link, every bookmark pointing at `/posts/hello-world` now returns `404`.

Self-healing URLs fix this. Enable it on the slug options, and the model's route key becomes `{slug}-{id}`.

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

Creating the post gives you a route key that carries both the slug and the id.

```php
$post = Post::create(['title' => 'Hello World']);
$post->getRouteKey(); // "hello-world-5"
```

Wire up a standard implicit route binding.

```php
Route::get('/posts/{post}', fn (Post $post) => $post);
```

Now rename the post to "Hello Universe". The canonical URL becomes `/posts/hello-universe-5`. The old URL still lands on the right page, with a `301` redirect to the new one.

| Request | Response |
| --- | --- |
| `GET /posts/hello-universe-5` | `200 OK` |
| `GET /posts/hello-world-5` | `301` redirect to `/posts/hello-universe-5` |
| `GET /posts/hello-world-99` | `404` when id `99` does not exist |

Read more in [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).
