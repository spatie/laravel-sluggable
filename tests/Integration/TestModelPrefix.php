<?php

namespace Spatie\Sluggable\Test\Integration;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class TestModelPrefix extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return $this->slugOptions ?? $this->getDefaultSlugOptions();
    }

    /**
     * Set the options for generating the slug.
     */
    public function setSlugOptions(SlugOptions $slugOptions) : self
    {
        $this->slugOptions = $slugOptions;

        return $this;
    }

    /**
     * Get the default slug options used in the tests.
     */
    public function getDefaultSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->prefixSlug('prefix')
            ->saveSlugsTo('url');
    }
}
