<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SelfHealingModel extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public static string $separator = '-';

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url')
            ->selfHealing(separator: static::$separator);
    }
}
