---
title: Generating your first slug
weight: 1
---

This walkthrough takes a `Post` model from nothing to a working `/posts/{slug}` URL in three steps: the `#[Sluggable]` attribute, a migration, and a query.

## 1. Add the attribute to the model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
    protected $fillable = ['title'];
}
```

That's it for setup. The package's service provider listens for Eloquent's `creating` and `updating` events and reads the attribute at runtime, so no trait or extra registration is needed.

## 2. Add a slug column to the migration

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

Resolve a model from a route by pointing the parameter at the slug column:

```php
Route::get('/posts/{post:slug}', fn (Post $post) => view('posts.show', compact('post')));
```

That is enough for most projects. From here you can:

- Tune the generation rules (separator, language, uniqueness, when to skip): see the rest of this section.
- Look slugs up directly with [`findBySlug()`](/docs/laravel-sluggable/v4/basic-usage/finding-models-by-slug).
- Keep old URLs working after a rename with [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).

## Let the Laravel Boost skill set things up for you

If your project uses [Laravel Boost](https://github.com/laravel/boost), this package ships a [Boost skill](/docs/laravel-sluggable/v4/laravel-boost-skill) that teaches Boost-aware AI assistants (Claude Code, Cursor, Copilot CLI, Gemini CLI, and others) how to scaffold all three steps above. Ask your assistant something like "set up sluggable on the Post model" and it will write the migration, add the attribute, and wire up the route binding.
