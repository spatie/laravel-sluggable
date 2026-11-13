---
title: Using the Sluggable attribute
weight: 2
---

This page documents every argument the `#[Sluggable]` attribute accepts. For setup, see [Generating your first slug](/docs/laravel-sluggable/v4/basic-usage/getting-started). For closures, translatable slugs, `findBySlug()`, and the other features that need the trait, see [Using the HasSlug trait](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait).

## `from`

The source column. Pass a single column name to slugify its value, or an array of column names to join them with the slug separator before slugification. Defaults to `'name'`. See [Combining multiple source columns](/docs/laravel-sluggable/v4/advanced-usage/source-fields).

## `to`

The column the generated slug is written to. Defaults to `'slug'`.

## `separator`

The character `Str::slug` uses to join words. Defaults to `'-'`.

## `language`

Language code passed to `Str::slug` for transliteration of non-ASCII characters. Defaults to `'en'`.

## `maxLength`

Truncates the slug before uniqueness resolution runs. Defaults to `250`. A uniqueness suffix may push the final value slightly past this limit.

## `unique`

When `true` (the default), the package appends a numeric suffix (`-1`, `-2`, etc.) on collisions so the slug column stays unique. Set to `false` to allow duplicates. To start the suffix from a different number or always force a suffix, see [Tuning the uniqueness suffix](/docs/laravel-sluggable/v4/advanced-usage/uniqueness).

## `onCreate`

When `true` (the default), a slug is generated as the model is first persisted. Set to `false` to leave the column untouched on create.

## `onUpdate`

When `true` (the default), the slug is regenerated whenever the source field changes on an update. Set to `false` for permalinks that should stay fixed after the initial create.

## `preventOverwrite`

When `true`, the package skips slug generation if the destination column already holds a value. Defaults to `false`. Useful when users provide their own slugs and the package should not stomp on them.

## `selfHealing`

Enables self-healing route keys. Defaults to `false`. Requires the `HasSlug` trait on the same class. See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).

## `selfHealingSeparator`

The string placed between the slug and the identifier in the self-healing route key. Defaults to `'-'`. See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).
