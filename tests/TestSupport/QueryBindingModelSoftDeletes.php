<?php

namespace Spatie\Sluggable\Tests\TestSupport;

class QueryBindingModelSoftDeletes extends SelfHealingModelSoftDeletes
{
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
