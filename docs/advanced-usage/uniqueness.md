---
title: Ensuring uniqueness
weight: 3
---

Slugs are unique by default. On a collision, the package appends a counter starting at 1: `this-is-an-example`, `this-is-an-example-1`, `this-is-an-example-2`.

## Allowing duplicates

```php
#[Sluggable(unique: false)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->allowDuplicateSlugs();
```

## Starting the counter at a different number

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->startSlugSuffixFrom(2);
```

## Always append a suffix, even on the first record

Instead of `this-is-an-example`, `this-is-an-example-1`, you get `this-is-an-example-1`, `this-is-an-example-2`.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->useSuffixOnFirstOccurrence();
```

## Custom suffix generator (trait only)

Replace the numeric counter with your own logic. The callable receives the base slug and the iteration number.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->usingSuffixGenerator(
        fn (string $slug, int $iteration) => bin2hex(random_bytes(4)),
    );
```
