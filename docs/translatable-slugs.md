---
title: Translatable slugs
weight: 5
---

Use the `HasTranslatableSlug` trait together with `spatie/laravel-translatable` to store one slug per locale.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;

    public $translatable = ['title', 'slug'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

The slug column must hold a JSON value; every translatable attribute in `$translatable` (including the slug itself) is stored as a locale-keyed object.

## Generating slugs from a callable per locale

When using a callable, instantiate `SlugOptions` with `createWithLocales()`. The callable receives the model and the current locale.

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::createWithLocales(['en', 'nl'])
        ->generateSlugsFrom(fn (Article $article, string $locale) => "{$locale} {$article->id}")
        ->saveSlugsTo('slug');
}
```

## Routing

Use Laravel's implicit route model binding, pointing at the slug column.

```php
Route::get('/articles/{article:slug}', fn (Article $article) => $article);
```

`HasTranslatableSlug` handles the locale-aware JSON lookup in `resolveRouteBindingQuery()`.

## Finding models by slug

`HasTranslatableSlug` ships its own `findBySlug()` that searches the current locale and the application fallback locale.

```php
$article = Article::findBySlug('my-article');
```

## Self-healing URLs

`selfHealing()` works with the translatable trait. The route key uses the slug for the current locale; stale slugs trigger the same redirect flow described in [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).

## Getting the route key for a specific locale

Use `getLocalizedRouteKey()` to retrieve the route key for a given locale without changing the model's active locale permanently.

```php
$article->getLocalizedRouteKey('nl'); // returns the route key for the 'nl' locale
```

The method temporarily sets the model locale inside a `try/finally` block, guaranteeing the original locale is always restored afterwards.
