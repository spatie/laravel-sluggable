<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlugWithUlids;
use Spatie\Sluggable\SlugOptions;

class UlidModel extends Model
{
    use HasSlugWithUlids;

    protected $guarded = [];

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }
}
