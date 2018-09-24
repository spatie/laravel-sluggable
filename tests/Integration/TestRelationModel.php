<?php

namespace Spatie\Sluggable\Test\Integration;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class TestRelationModel extends Model
{
    use HasSlug;

    protected $table = 'test_relation_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['testModel.name', 'testModel.other_field'])
            ->saveSlugsTo('reference');
    }

    public function testModel()
    {
        return $this->belongsTo(TestModel::class);
    }
}
