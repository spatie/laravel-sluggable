# Generate slugs when saving Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-sluggable/run-tests.yml)](https://github.com/spatie/laravel-sluggable/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)

This package generates a unique slug for any Eloquent model whenever it is created or updated. Configure it with a `#[Sluggable]` attribute on the model, or with the `HasSlug` trait and a `getSlugOptions()` method when you need more control.

```php
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
}

$post = Post::create(['title' => 'activerecord is awesome']);
$post->slug; // "activerecord-is-awesome"
```

Slugs are produced by Laravel's `Str::slug` helper. On top of generation, the package ships uniqueness resolution, translatable slugs (via `spatie/laravel-translatable`), self-healing URLs, and a set of overridable actions for the low-level work.

The package also bundles a [Laravel Boost](https://github.com/laravel/boost) skill so AI assistants know how to use it. Boost discovers the skill automatically once both packages are installed. Read more in the [Laravel Boost skill](https://spatie.be/docs/laravel-sluggable/v4/laravel-boost-skill) docs.

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
