<?php


namespace Spatie\Sluggable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UuidTestModel extends Model
{
    use HasSlug;

    protected $guarded = [];

    protected $table = 'uuid_test_models';

    public $incrementing = false;

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }
}
