<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasTranslatableSlugWithUlids
{
    use HasTranslatableSlug;
    use HasUlids {
        HasTranslatableSlug::resolveRouteBindingQuery insteadof HasUlids;
        HasUlids::resolveRouteBindingQuery as protected resolveUlidRouteBindingQuery;
    }

    protected function resolveModelRouteBindingQuery($query, mixed $value, ?string $field = null): Builder|Relation
    {
        return $this->resolveUlidRouteBindingQuery($query, $value, $field);
    }
}
