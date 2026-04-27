---
title: Finding models by slug
weight: 4
---

The `HasSlug` trait adds a static `findBySlug()` helper that queries the slug column configured through `saveSlugsTo()`.

```php
$article = Article::findBySlug('my-article');
```

The second argument is a list of columns to select, matching Eloquent's `find()`:

```php
Article::findBySlug('my-article', ['id', 'slug', 'title']);
```

A third argument takes a callable that can add extra constraints to the query before it runs:

```php
Article::findBySlug('my-article', ['*'], fn ($query) => $query->where('published', true));
```

For translatable slugs, `HasTranslatableSlug` ships its own `findBySlug()` that looks in both the current locale and the application fallback locale.
