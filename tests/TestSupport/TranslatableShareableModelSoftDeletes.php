<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasShareableTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class TranslatableShareableModelSoftDeletes extends Model
{
    use HasTranslations;
    use HasShareableTranslatableSlug;
    use SoftDeletes;

    protected $table = 'translatable_shareable_models_soft_deletes';

    protected $guarded = [];
    public $timestamps = false;

    protected array $translatable = ['name', 'other_field'];

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

    public function testModel(): BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }
}
