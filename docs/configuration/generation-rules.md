---
title: When slugs are generated
weight: 4
---

Slugs are generated on both create and update by default. Every aspect of that can be turned off.

## Skip on create

```php
#[Sluggable(onCreate: false)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->doNotGenerateSlugsOnCreate();
```

## Skip on update

Useful for permalinks that should stay fixed even when the source field changes.

```php
#[Sluggable(onUpdate: false)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->doNotGenerateSlugsOnUpdate();
```

```php
$post = Post::create(['name' => 'my name']);
$post->slug; // "my-name"

$post->name = 'changed name';
$post->save();
$post->slug; // still "my-name"
```

## Skip based on model state (trait only)

`skipGenerateWhen()` takes a callable. If it returns `true`, no slug is generated for this save.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->skipGenerateWhen(fn () => $this->state === 'draft');
```

## Preventing overwrites

When `preventOverwrite` is enabled, the package will not generate a slug if the destination column already holds a value.

```php
#[Sluggable(preventOverwrite: true)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->preventOverwrite();
```
