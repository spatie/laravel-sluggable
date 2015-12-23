<?php

namespace Spatie\Sluggable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Sluggable;
use Spatie\Sluggable\SlugOptions;

class TestModel extends Model implements Sluggable
{
    use GeneratesSlugs;
    protected $table = 'test_models';
    protected $guarded = [];
    public $timestamps = false;

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugFrom('name')
            ->saveSlugTo('url')
            ->duplicateSlugsAreOk()
            ->slugShouldBeNoLongerThan(255);
    }
}
