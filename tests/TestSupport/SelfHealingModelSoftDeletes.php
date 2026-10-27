<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SelfHealingModelSoftDeletes extends Model
{
    use HasSlug;
    use SoftDeletes;

    protected $table = 'test_model_soft_deletes';

    protected $guarded = [];

    public $timestamps = false;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url')
            ->selfHealing();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
