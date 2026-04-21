---
title: Using the Sluggable attribute
weight: 1
---

For most models, a single `#[Sluggable]` attribute on the class is enough. A wildcard Eloquent event listener in the package's service provider picks up the attribute at runtime and generates slugs when models are created or updated.

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

The attribute constructor accepts the same options that `SlugOptions` exposes, as long as they can be expressed as attribute arguments. Callables, closures, and dynamic database scopes cannot go in an attribute, so features that require them fall back to the trait (see [Using the HasSlug trait](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait)).

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

## When to use the trait instead

Reach for the trait when you need any of the following:

- A callable passed to `generateSlugsFrom()` that computes the slug from arbitrary model state.
- `skipGenerateWhen(fn () => ...)`, `extraScope(fn ($query) => ...)`, or `usingSuffixGenerator(fn () => ...)`.
- Translatable slugs through `HasTranslatableSlug`.
- The `findBySlug()` helper.
- Self-healing URLs. The attribute alone cannot override `getRouteKey()` and `resolveRouteBinding()`, so `selfHealing: true` requires the trait on the same class.

If both are present on a model, the trait wins and the attribute is ignored.
