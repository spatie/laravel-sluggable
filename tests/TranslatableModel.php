<?php

namespace Spatie\Sluggable\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    protected $translatable = ['name', 'other_field', 'slug'];

    protected $customSlugOptions;

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

    public function testModel(): BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }
}
