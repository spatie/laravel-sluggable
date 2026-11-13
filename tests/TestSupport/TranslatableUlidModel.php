<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlugWithUlids;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class TranslatableUlidModel extends Model
{
    use HasTranslatableSlugWithUlids;
    use HasTranslations;

    protected $table = 'ulid_models';

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
