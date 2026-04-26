---
title: Using the Sluggable attribute
weight: 2
---

A single `#[Sluggable]` attribute on the class is enough for most models. A wildcard Eloquent event listener in the package's service provider picks the attribute up at runtime and generates slugs when models are created or updated.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
}
```

```php
$post = Post::create(['title' => 'Hello World']);
$post->slug; // "hello-world"
```

## Supported arguments

The attribute constructor mirrors the `SlugOptions` builder for the options that can be expressed as attribute arguments.

| Argument | Default | Description |
| --- | --- | --- |
| `from` | `'name'` | Source column, or array of columns joined with the separator. |
| `to` | `'slug'` | Destination column. |
| `separator` | `'-'` | Separator used by `Str::slug`. |
| `language` | `'en'` | Language code passed to `Str::slug`. |
| `maxLength` | `250` | Truncates the slug before uniqueness resolution. |
| `unique` | `true` | Appends a numeric suffix on collisions. |
| `onCreate` | `true` | Generate when the model is created. |
| `onUpdate` | `true` | Regenerate when the model is updated. |
| `preventOverwrite` | `false` | Do not regenerate when the destination column is already filled. |
| `selfHealing` | `false` | Turn the route key into `slug-id` (requires the `HasSlug` trait). |
| `selfHealingSeparator` | `'-'` | Separator used between slug and identifier. |

## What the attribute can't do

A few things need the [`HasSlug` trait](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait) instead of (or alongside) the attribute, because they need closures, instance methods, or both:

- Closures: `generateSlugsFrom(fn ($model) => ...)`, `skipGenerateWhen(fn () => ...)`, `extraScope(fn ($query) => ...)`, `usingSuffixGenerator(fn () => ...)`.
- Translatable slugs through `HasTranslatableSlug`.
- The `findBySlug()` static helper.
- Self-healing URLs need the trait on the same class so it can override `getRouteKey()` and `resolveRouteBinding()`. The attribute's `selfHealing: true` works once the trait is present.

If a model has both, the trait's `getSlugOptions()` wins and the attribute is ignored.
