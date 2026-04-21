---
title: Overview
weight: 1
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
