<?php

namespace Spatie\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class TranslatableModelSoftDeletes extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;
    use SoftDeletes;

    protected $table = 'translatable_model_soft_deletes';

    protected $guarded = [];
    public $timestamps = false;

    public $translatable = ['name', 'other_field', 'slug'];

    private $customSlugOptions;

    public function useSlugOptions($slugOptions)
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
