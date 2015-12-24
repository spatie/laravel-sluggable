# Generate slugs when saving Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-sluggable/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-sluggable)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/a56f8c11-331f-4d3c-8724-77f55969f2f7.svg?style=flat-square)](https://insight.sensiolabs.com/projects/a56f8c11-331f-4d3c-8724-77f55969f2f7)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-sluggable.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-sluggable)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)

This package provides as trait that will generate a unique slug when saving model. The trait can be applied on any model. 

```php
$model = new EloquentModel()
$model->name = 'activerecord is awesome';
$model->save();

echo $model->url //ouputs "activerecord-is-awesome"
```

The slug will be generated with Laravels `str_slug`-method. Spaces will be converted to '-'.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Installation

You can install the package via composer:
``` bash
$ composer require spatie/laravel-sluggable
```

## Usage

The package provides a `Spatie\Sluggable\HasSlug`-trait that can be applied on any Eloquent model. 


The trait contains an abstract method `getSlugOptions()` that you must implement yourself. 

This is the most simple implementation:

```php
use Spatie\Sluggable\Slug

public function getDefaultSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url');
}
```

Want to use multiple field as the basis for a slug? No problem!

```php
use Spatie\Sluggable\Slug

public function getDefaultSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(['first_name', 'last_name'])
        ->saveSlugsTo('url');
}
```

By default the package will generate unique slugs by appending '-' and a number to a slug that already exists.

You can disable this behaviour by calling `allowDuplicateSlugs`.

```php
use Spatie\Sluggable\Slug

public function getDefaultSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->allowDuplicateSlugs();
}
```

You can also put a maximum size limit on the created slug.

```php
use Spatie\Sluggable\Slug

public function getDefaultSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->slugsShouldBeNoLongerThan(50);
}
```

The slug can be slightly longer that the value specified as the suffix to make it unique is added.


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
