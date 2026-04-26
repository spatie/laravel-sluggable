---
title: Controlling when slugs are generated
weight: 4
---

Slugs are generated on both create and update by default. Every aspect of that can be turned off.

## Skip on create

Set `onCreate` to `false` (or call `doNotGenerateSlugsOnCreate()`) to leave the slug column untouched when a model is first persisted.

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

`skipGenerateWhen()` takes a closure. It runs on every save (create or update). If it returns `true`, no slug is generated for that save.

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->skipGenerateWhen(fn () => $this->state === 'draft');
```

Because the closure is defined inside `getSlugOptions()` on the model, `$this` refers to the model and is re-evaluated on every save.

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
