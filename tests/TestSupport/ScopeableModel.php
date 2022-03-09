<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ScopeableModel extends Model
{
    use HasSlug;

    protected $table = 'scopeable_models';

    protected $guarded = [];
    public $timestamps = false;

    public array $translatable = ['name', 'slug', 'scope_id'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->extraScope(fn ($builder) => $builder->where('scope_id', $this->scope_id));
    }
}
