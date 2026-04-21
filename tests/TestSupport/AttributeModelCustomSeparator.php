<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'name', to: 'url', separator: '_')]
class AttributeModelCustomSeparator extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}
