<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasSlugWithUuids
{
    use HasSlug;
    use HasUuids {
        HasSlug::resolveRouteBindingQuery insteadof HasUuids;
        HasUuids::resolveRouteBindingQuery as protected resolveUuidRouteBindingQuery;
    }

    protected function resolveModelRouteBindingQuery($query, mixed $value, ?string $field = null): Builder|Relation
    {
        return $this->resolveUuidRouteBindingQuery($query, $value, $field);
    }
}
