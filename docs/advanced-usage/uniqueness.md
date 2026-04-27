---
title: Tuning the uniqueness suffix
weight: 2
---

Slugs are unique by default. On a collision, the package appends a counter starting at `1` (for example `this-is-an-example`, `this-is-an-example-1`, `this-is-an-example-2`). Two builder methods change how that suffix is generated. They live on `SlugOptions` and are not available through the attribute.

## Starting the counter at a different number

`startSlugSuffixFrom()` sets the first numeric suffix used on a collision.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->startSlugSuffixFrom(2);
```

## Always append a suffix, even on the first record

`useSuffixOnFirstOccurrence()` adds a suffix even when the slug would otherwise be unique. Instead of `this-is-an-example`, `this-is-an-example-1`, you get `this-is-an-example-1`, `this-is-an-example-2`.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->useSuffixOnFirstOccurrence();
```

For a non-numeric or otherwise custom suffix, use the trait's `usingSuffixGenerator()`. See [A custom suffix generator](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait#a-custom-suffix-generator).
