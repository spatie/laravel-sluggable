<?php

use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;

it('generates a free suffix with an underscore separator on SQLite', function () {
    $createModel = function (): TestModel {
        return new class extends TestModel
        {
            public function getSlugOptions(): SlugOptions
            {
                return SlugOptions::create()
                    ->generateSlugsFrom('name')
                    ->saveSlugsTo('url')
                    ->usingSeparator('_');
            }
        };
    };

    foreach (range(1, 2) as $ignored) {
        $model = $createModel();
        $model->name = 'foo bar';
        $model->save();
    }

    $model = $createModel();
    $model->name = 'foo bar';
    $model->save();

    expect($model->url)->toEqual('foo_bar_2');
});
