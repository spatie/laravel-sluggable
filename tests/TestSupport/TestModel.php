<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TestModel extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return $this->slugOptions ?? $this->getDefaultSlugOptions();
    }

    public function setSlugOptions(SlugOptions $slugOptions): self
    {
        $this->slugOptions = $slugOptions;

        return $this;
    }

    public function getDefaultSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url');
    }

    public function translatableModels(): HasMany
    {
        return $this->hasMany(TranslatableModel::class);
    }
}
