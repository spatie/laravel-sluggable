<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class SelfHealingTranslatableModel extends Model
{
    use HasTranslatableSlug;
    use HasTranslations;

    protected $table = 'translatable_models';

    protected $guarded = [];

    public $timestamps = false;

    protected array $translatable = ['name', 'slug'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->selfHealing();
    }
}
