---
title: Choosing source fields
weight: 1
---

A slug can be generated from one column, several columns joined together, or a callable.

## A single column

```php
#[Sluggable(from: 'title')]
```

```php
SlugOptions::create()->generateSlugsFrom('title');
```

## Multiple columns

Pass an array and the columns are joined using the slug separator.

```php
#[Sluggable(from: ['first_name', 'last_name'])]
```

```php
SlugOptions::create()->generateSlugsFrom(['first_name', 'last_name']);
```

```php
$author = Author::create(['first_name' => 'John', 'last_name' => 'Doe']);
$author->slug; // "john-doe"
```

## A callable (trait only)

When you need arbitrary logic (concatenations, string transformations, reading related models), pass a callable to `generateSlugsFrom()`. Callables cannot go in attribute arguments, so this path requires the trait.

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(fn (Post $post) => "{$post->author->name} {$post->title}")
        ->saveSlugsTo('slug');
}
```
