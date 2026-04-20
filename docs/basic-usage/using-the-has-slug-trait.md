---
title: Using the HasSlug trait
weight: 2
---

Use the `HasSlug` trait when you need options the attribute cannot express, such as callables, dynamic database scopes, translatable slugs, or self-healing URLs. The trait requires a `getSlugOptions()` method that returns a `SlugOptions` instance.

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

## The migration

Your model's table needs a column that matches the name passed to `saveSlugsTo()`.

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
            $table->string('slug');
            $table->string('title');
            $table->timestamps();
        });
    }
};
```

## Regenerating a slug on demand

The trait exposes a public `generateSlug()` method that forces regeneration. Call `save()` afterwards to persist it.

```php
$post->generateSlug();
$post->save();
```

## Overriding a generated slug

Assigning a value to the slug column and saving the model bypasses automatic generation for that operation.

```php
$post = Post::create(['title' => 'My name']);
$post->slug; // "my-name"

$post->slug = 'my-custom-url';
$post->save();

$post->slug; // "my-custom-url"
```
