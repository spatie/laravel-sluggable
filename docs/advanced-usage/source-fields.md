---
title: Combining multiple source columns
weight: 1
---

Pass an array to `from` (or `generateSlugsFrom()`) and the columns are concatenated using the slug separator before `Str::slug` runs.

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

For a slug derived from arbitrary logic (concatenations, related models, conditional values), use the trait and pass a closure to `generateSlugsFrom()`. See [A closure as the source field](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait#a-closure-as-the-source-field).
