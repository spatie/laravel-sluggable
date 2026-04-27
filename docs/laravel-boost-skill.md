---
title: Laravel Boost skill
weight: 7
---

This package ships a [Laravel Boost](https://github.com/laravel/boost) skill that teaches any Boost-aware AI assistant (Claude Code, Cursor, Copilot CLI, Gemini CLI, and others supported by Boost) how to use `laravel-sluggable` correctly.

## Discovery

When your project has both `spatie/laravel-sluggable` and `laravel/boost` installed, Boost's `SkillComposer` automatically discovers the skill at `vendor/spatie/laravel-sluggable/resources/boost/skills/sluggable-development/`. No extra configuration is required.

Running Boost's install command writes the skill into your configured agent's skills directory (for example, `.claude/skills/sluggable-development/` for Claude Code or `.agents/skills/sluggable-development/` for Gemini CLI).

## What the skill covers

The skill activates when a query mentions slugs, permalinks, the `HasSlug` trait, the `HasTranslatableSlug` trait, the `#[Sluggable]` attribute, `SlugOptions`, `findBySlug`, self-healing URLs, or stale slug redirects. It guides the assistant through:

- Choosing between the `#[Sluggable]` attribute and the `HasSlug` trait for a given model.
- Generating the migration for a slug column, including the `nullable` then unique backfill pattern and the JSON column requirement for translatable slugs.
- Configuring separator, length, language, uniqueness behavior, and scope.
- Wiring implicit route binding through the slug column.
- Enabling self-healing URLs, customizing the separator, and overriding the `308` redirect through the `SelfHealing` facade.
- Swapping the default action classes via `config/sluggable.php`.

The full skill content lives at [`resources/boost/skills/sluggable-development/SKILL.md`](https://github.com/spatie/laravel-sluggable/blob/main/resources/boost/skills/sluggable-development/SKILL.md) in the package repository.
