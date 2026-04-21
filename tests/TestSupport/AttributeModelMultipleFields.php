<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: ['name', 'other_field'], to: 'url', maxLength: 60)]
class AttributeModelMultipleFields extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}
