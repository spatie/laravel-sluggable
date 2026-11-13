<?php

namespace Spatie\Sluggable\Tests\TestSupport;

class QueryBindingModel extends SelfHealingModel
{
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
