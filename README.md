# Generate slugs when saving Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-sluggable/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-sluggable)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/a56f8c11-331f-4d3c-8724-77f55969f2f7.svg?style=flat-square)](https://insight.sensiolabs.com/projects/a56f8c11-331f-4d3c-8724-77f55969f2f7)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-sluggable.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-sluggable)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-sluggable)

This package provides a trait that will generate a unique slug when saving any Eloquent model. 

```php
$model = new EloquentModel();
$model->name = 'activerecord is awesome';
$model->save();

echo $model->slug; // ouputs "activerecord-is-awesome"
```

The slugs are generated with Laravels `str_slug` method, whereby spaces are converted to '-'.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

## Installation

You can install the package via composer:
``` bash
$ composer require spatie/laravel-sluggable
```

## Usage

Your Eloquent models should use the `Spatie\Sluggable\HasSlug` trait and the `Spatie\Sluggable\SlugOptions` class.

The trait contains an abstract method `getSlugOptions()` that you must implement yourself. 

Here's an example of how to implement the trait:

```php
<?php

namespace App;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class YourEloquentModel extends Model
{
    use HasSlug;
    
    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }
}
```

Want to use multiple field as the basis for a slug? No problem!

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(['first_name', 'last_name'])
        ->saveSlugsTo('url');
}
```

You can also pass a `callable` to `generateSlugsFrom`.


By default the package will generate unique slugs by appending '-' and a number, to a slug that already exists.

You can disable this behaviour by calling `allowDuplicateSlugs`.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->allowDuplicateSlugs();
}
```

You can also put a maximum size limit on the created slug:

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->slugsShouldBeNoLongerThan(50);
}
```

The slug may be slightly longer than the value specified, due to the suffix which is added to make it unique.

You can also override the generated slug just by setting it to another value then the generated slug.
```php
$model = EloquentModel:create(['name' => 'my name']); //url is now "my-name"; 
$model->url = 'my-custom-url';
$model-save();

$model->name = 'changed name';
$model->save(); //url stays "my name"
```

If you don't want to create the slug when the model is initially created you can set use the doNotGenerateSlugOnCreate() flag the slug options.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->doNotGenerateSlugOnCreate()
}
```

Similarly, if you want to prevent the slug from being updated on model updates, call doNotGenerateSlugOnUpdate(). This can be helpful for creating permalinks that don't change until you explicitly want it to.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->doNotGenerateSlugOnUpdate()
}
```

If you want to explicitly update the slug on the model you can call `generateSlug()` on your model at any time to make the slug according to your other options. Don't forget to `save()` the model to persist the update to your database.

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
