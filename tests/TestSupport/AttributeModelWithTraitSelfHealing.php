<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[Sluggable(from: 'name', to: 'url', selfHealing: true)]
class AttributeModelWithTraitSelfHealing extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url')
            ->selfHealing();
    }
}
