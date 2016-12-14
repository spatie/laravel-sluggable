<?php

namespace Edofre\Sluggable\Test\Integration;

use Edofre\Sluggable\HasSlug;
use Edofre\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TestModel
 * @package Edofre\Sluggable\Test\Integration
 */
class TestModel extends Model
{
    use HasSlug;

    /** @var bool */
    public $timestamps = false;
    /** @var string */
    protected $table = 'test_models';
    /** @var array */
    protected $guarded = [];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions()
    {
        return isset($this->slugOptions) ? $this->slugOptions : $this->getDefaultSlugOptions();
    }

    /**
     * Get the default slug options used in the tests.
     */
    public function getDefaultSlugOptions()
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }

    /**
     * Set the options for generating the slug.
     * @param SlugOptions $slugOptions
     * @return $this
     */
    public function setSlugOptions(SlugOptions $slugOptions)
    {
        $this->slugOptions = $slugOptions;
        return $this;
    }
}
