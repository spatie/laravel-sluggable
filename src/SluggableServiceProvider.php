<?php

namespace Spatie\Sluggable;

use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SluggableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-sluggable')
            ->hasMigration('create_slug_table');
    }
}
