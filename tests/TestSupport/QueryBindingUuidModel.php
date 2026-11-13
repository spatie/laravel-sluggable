<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Spatie\Sluggable\SlugOptions;

class QueryBindingUuidModel extends UuidModel
{
    protected $table = 'uuid_models';

    public function getSlugOptions(): SlugOptions
    {
        return parent::getSlugOptions()->selfHealing(separator: '--');
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
