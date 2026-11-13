<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Spatie\Sluggable\SlugOptions;

class QueryBindingUlidModel extends UlidModel
{
    protected $table = 'ulid_models';

    public function getSlugOptions(): SlugOptions
    {
        return parent::getSlugOptions()->selfHealing(separator: '--');
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
