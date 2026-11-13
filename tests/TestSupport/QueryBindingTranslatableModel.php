<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class QueryBindingTranslatableModel extends SelfHealingTranslatableModel
{
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        return $this->resolveSelfHealingRouteBindingQuery($query, $value, $field);
    }
}
