---
title: Separator, length, and language
weight: 2
---

## Separator

The default separator is `-`. Pass `separator` to the attribute or call `usingSeparator()` on `SlugOptions`.

```php
#[Sluggable(separator: '_')]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->usingSeparator('_');
```

## Maximum length

The slug is truncated before uniqueness resolution. The default is 250 characters.

```php
#[Sluggable(maxLength: 60)]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->slugsShouldBeNoLongerThan(60);
```

A uniqueness suffix may push the final string slightly past this limit.

## Language

`Str::slug()` uses a language map to transliterate non-ASCII characters. The default is `en`.

```php
#[Sluggable(language: 'nl')]
```

```php
SlugOptions::create()
    ->generateSlugsFrom('name')
    ->saveSlugsTo('slug')
    ->usingLanguage('nl');
```
