---
title: Using the Sluggable attribute
weight: 2
---

This page documents every argument the `#[Sluggable]` attribute accepts. For setup, see [Generating your first slug](/docs/laravel-sluggable/v4/basic-usage/getting-started). For closures, translatable slugs, `findBySlug()`, and the other features that need the trait, see [Using the HasSlug trait](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait).

## `from`

The source column. Pass a single column name to slugify its value, or an array of column names to join them with the slug separator before slugification. Defaults to `'name'`. See [Choosing source fields](/docs/laravel-sluggable/v4/advanced-usage/source-fields).

## `to`

The column the generated slug is written to. Defaults to `'slug'`.

## `separator`

The character `Str::slug` uses to join words. Defaults to `'-'`. See [Configuring separator, length, and language](/docs/laravel-sluggable/v4/advanced-usage/separator-length-and-language).

## `language`

Language code passed to `Str::slug` for transliteration of non-ASCII characters. Defaults to `'en'`.

## `maxLength`

Truncates the slug before uniqueness resolution runs. Defaults to `250`. A uniqueness suffix may push the final value slightly past this limit.

## `unique`

When `true` (the default), the package appends a numeric suffix (`-1`, `-2`, etc.) on collisions so the slug column stays unique. Set to `false` to allow duplicates. See [Ensuring uniqueness](/docs/laravel-sluggable/v4/advanced-usage/uniqueness).

## `onCreate`

When `true` (the default), a slug is generated as the model is first persisted. Set to `false` to leave the column untouched on create. See [Controlling when slugs are generated](/docs/laravel-sluggable/v4/advanced-usage/generation-rules).

## `onUpdate`

When `true` (the default), the slug is regenerated whenever the source field changes on an update. Set to `false` for permalinks that should stay fixed after the initial create.

## `preventOverwrite`

When `true`, the package skips slug generation if the destination column already holds a value. Defaults to `false`. Useful when users provide their own slugs and the package should not stomp on them.

## `selfHealing`

Turns the model's route key into `{slug}{selfHealingSeparator}{id}`. The slug rides along in the URL, but the primary key drives the lookup, so renaming a model never breaks an old link. Stale URLs `301`-redirect to the canonical one. Defaults to `false`. See [Self-healing URLs](/docs/laravel-sluggable/v4/basic-usage/self-healing-urls).

**Requires the `HasSlug` trait on the same class.** Self-healing has to override `getRouteKey()` and `resolveRouteBinding()`, which an attribute alone cannot do. Setting `selfHealing: true` without `use HasSlug;` throws a `SelfHealingRequiresTrait` exception.

## `selfHealingSeparator`

The string placed between the slug and the identifier in the self-healing route key. Defaults to `'-'`. Use something that cannot appear at the end of a slug (for example `'--'`) when your slugs can themselves end in a digit. Same trait requirement as `selfHealing`.

## What the attribute can't do

A few features need the [`HasSlug` trait](/docs/laravel-sluggable/v4/basic-usage/using-the-has-slug-trait) instead of (or, for self-healing, alongside) the attribute, because they need closures or instance methods:

- Closures: `generateSlugsFrom(fn ($model) => ...)`, `skipGenerateWhen(fn () => ...)`, `extraScope(fn ($query) => ...)`, `usingSuffixGenerator(fn () => ...)`.
- Translatable slugs through `HasTranslatableSlug`.
- The `findBySlug()` static helper.

If a model has both, the trait's `getSlugOptions()` wins and the attribute is ignored.
