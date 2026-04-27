<div align="left">
    <a href="https://spatie.be/open-source?utm_source=github&utm_medium=banner&utm_campaign=laravel-sluggable">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://spatie.be/packages/header/laravel-sluggable/html/dark.webp">
        <img alt="Logo for laravel-sluggable" src="https://spatie.be/packages/header/laravel-sluggable/html/light.webp">
      </picture>
    </a>

<h1>Generate slugs when saving Eloquent models</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-sluggable/run-tests.yml)](https://github.com/spatie/laravel-sluggable/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)
    
</div>

This package generates a unique slug for any Eloquent model whenever it is created or updated. Add a `#[Sluggable]` attribute to the model and the package handles the rest.

```php
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
}

$post = Post::create(['title' => 'activerecord is awesome']);
$post->slug; // "activerecord-is-awesome"
```

For features that need closures (custom source callables, scoped uniqueness, conditional skip, custom suffix generators) use the `HasSlug` trait with a `getSlugOptions()` method instead.

## Highlights

- **Unique slugs out of the box**, with a configurable `-1`, `-2`, ... suffix on collisions.
- **Self-healing URLs**: route keys that combine the slug with the primary key (`hello-world-5`) so renaming a model never breaks an existing link. Stale slugs return a `308` redirect to the canonical URL.
- **Translatable slugs** through `HasTranslatableSlug` and `spatie/laravel-translatable`.
- **Overridable actions**: swap the slug generator or the self-healing URL logic for your own class via a config file.
- **Laravel Boost skill** bundled with the package, so AI assistants know how to scaffold sluggable models in your project. Boost discovers it automatically once both packages are installed.

Self-healing URLs combine the slug with the primary key. The `HasSlug` trait is required alongside the attribute, because it overrides Eloquent's route key and route binding methods.

```php
#[Sluggable(from: 'title', to: 'slug', selfHealing: true)]
class Post extends Model
{
    use HasSlug;
}

// /posts/hello-world-5   → 200
// /posts/old-title-5     → 308 to /posts/hello-world-5
```

Spatie is a web design agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-sluggable.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-sluggable)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

All documentation is available [on our documentation site](https://spatie.be/docs/laravel-sluggable).

## Installation

```bash
composer require spatie/laravel-sluggable
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
