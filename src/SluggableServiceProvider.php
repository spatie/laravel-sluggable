<?php

namespace Spatie\Sluggable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SluggableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-sluggable')
            ->hasMigration('create_slug_table');
    }
}
