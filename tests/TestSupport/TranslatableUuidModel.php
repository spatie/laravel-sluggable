<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlugWithUuids;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class TranslatableUuidModel extends Model
{
    use HasTranslatableSlugWithUuids;
    use HasTranslations;

    protected $table = 'uuid_models';

    protected $guarded = [];

    public $timestamps = false;

    protected array $translatable = ['name', 'url'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url')
            ->selfHealing(separator: '--');
    }
}
