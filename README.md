# I just converted it to work with php 5.6.
## This is a fork from https://github.com/spatie/laravel-sluggable 

# Generate slugs when saving Eloquent models

This package provides a trait that will generate a unique slug when saving any Eloquent model. 

```php
$model = new EloquentModel();
$model->name = 'activerecord is awesome';
$model->save();

echo $model->slug; // ouputs "activerecord-is-awesome"
```

The slugs are generated with Laravels `str_slug` method, whereby spaces are converted to '-'.

## Installation

You can install the package via composer:
``` bash
$ composer require edofre/laravel-sluggable
```

## Usage

Your Eloquent models should use the `Edofre\Sluggable\HasSlug` trait and the `Edofre\Sluggable\SlugOptions` class.

The trait contains an abstract method `getSlugOptions()` that you must implement yourself. 

Here's an example of how to implement the trait:

```php
<?php

namespace App;

use Edofre\Sluggable\HasSlug;
use Edofre\Sluggable\SlugOptions;
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

If you don't want to create the slug when the model is initially created you can set use the `doNotGenerateSlugsOnCreate() function.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->doNotGenerateSlugsOnCreate();
}
```

Similarly, if you want to prevent the slug from being updated on model updates, call `doNotGenerateSlugsOnUpdate()`. This can be helpful for creating permalinks that don't change until you explicitly want it to.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->doNotGenerateSlugsOnUpdate();
}
```

If you want to explicitly update the slug on the model you can call `generateSlug()` on your model at any time to make the slug according to your other options. Don't forget to `save()` the model to persist the update to your database.

## Change log

Please see [CHANGELOG](CHANGE.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)

## This is a fork from https://github.com/spatie/laravel-sluggable, I just converted it to work with php 5.6
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
