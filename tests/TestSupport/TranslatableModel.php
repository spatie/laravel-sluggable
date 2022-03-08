<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class TranslatableModel extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;

    protected $table = 'translatable_models';

    protected $guarded = [];
    public $timestamps = false;

    public array $translatable = ['name', 'other_field', 'slug'];

    private ?SlugOptions $customSlugOptions = null;

    public function useSlugOptions(SlugOptions $slugOptions)
    {
        $this->customSlugOptions = $slugOptions;
    }

    public function getSlugOptions(): SlugOptions
    {
        return $this->customSlugOptions ?: SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
