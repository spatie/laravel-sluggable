---
title: Generating your first slug
weight: 1
---

This walkthrough takes a `Post` model from nothing to a working slug in three steps: the `#[Sluggable]` attribute, a migration, and a query. An optional fourth step wires the slug into a route with self-healing URLs so renaming a model never breaks an old link.

## 1. Add the attribute to the model

Place `#[Sluggable]` on the model class and tell it which column to read from and which to write to.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
}
```

That's all the configuration the package needs. Its service provider listens for Eloquent's `creating` and `updating` events and reads the attribute at runtime, so no trait or extra registration is required.

## 2. Add a slug column to the migration

The package writes the slug into the column you named in the attribute, so the table needs a matching string column.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }
};
```

The `unique()` constraint is optional. The package appends `-1`, `-2`, etc. on collisions whether or not the database enforces uniqueness, but the constraint is a useful safety net for code paths that bypass Eloquent.

## 3. Use it

Slugs are written when the model is created and regenerated whenever the source field changes.

```php
$post = Post::create(['title' => 'Hello World']);
$post->slug; // "hello-world"

$post->update(['title' => 'Hello Universe']);
$post->slug; // "hello-universe"
```

## 4. Wire it into a route with self-healing (optional)

This step is optional. Skip it if you don't need slugs in your URLs, or if your slugs genuinely never change after creation (see [Using slugs in routes](/docs/laravel-sluggable/v4/basic-usage/using-slugs-in-routes) for plain `{post:slug}` binding).

Most user-editable content (posts, products, profiles) eventually gets renamed, and a renamed slug breaks every existing link unless you opt into self-healing URLs. With self-healing enabled the route key becomes `{slug}-{id}`, the primary key drives the lookup, and a stale slug `301`-redirects to the canonical URL instead of returning a `404`.

To follow along, add `selfHealing: true` to the attribute on the `Post` model from step 1 and add `use HasSlug;` to the class. The trait is required because self-healing has to override `getRouteKey()` and `resolveRouteBinding()`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\HasSlug;

#[Sluggable(
    from: 'title',
    to: 'slug',
    selfHealing: true,
)]
class Post extends Model
{
    use HasSlug;
}
```

Bind the model to a route the usual way; the package handles the route key for you.

```php
// routes/web.php
Route::get('/posts/{post}', fn (Post $post) => view('posts.show', compact('post')));
```

A request to `/posts/hello-world-5` resolves the post; renaming it later doesn't break the link. See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls) for the full mechanics, or [Using slugs in routes](/docs/laravel-sluggable/v4/basic-usage/using-slugs-in-routes) for plain `{post:slug}` binding when the slug genuinely never changes.

## What's next

That is enough for most projects. From here you can:

- Tune the generation rules (separator, language, uniqueness, when to skip): see the rest of this section.
- Look slugs up directly with [`findBySlug()`](/docs/laravel-sluggable/v4/basic-usage/finding-models-by-slug).
- Translate slugs per locale with [`HasTranslatableSlug`](/docs/laravel-sluggable/v4/translatable-slugs).

## Let the Laravel Boost skill set things up for you

If your project uses [Laravel Boost](https://github.com/laravel/boost), this package ships a [Boost skill](/docs/laravel-sluggable/v4/laravel-boost-skill) that teaches Boost-aware AI assistants (Claude Code, Cursor, Copilot CLI, Gemini CLI, and others) how to scaffold every step above, including the optional self-healing route binding. Ask your assistant something like "set up sluggable on the Post model" and it will write the migration, add the attribute, and wire up the route.
