<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TestModelSoftDeletes extends Model
{
    use HasSlug;
    use SoftDeletes;

    protected $table = 'test_model_soft_deletes';

    protected $guarded = [];

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return $this->slugOptions ?? $this->getDefaultSlugOptions();
    }

    public function setSlugOptions(SlugOptions $slugOptions): self
    {
        $this->slugOptions = $slugOptions;

        return $this;
    }

    public function getDefaultSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }
}
