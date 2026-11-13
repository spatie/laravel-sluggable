<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Spatie\Sluggable\SlugOptions;

class SelfHealingUlidModel extends UlidModel
{
    protected $table = 'ulid_models';

    public function getSlugOptions(): SlugOptions
    {
        return parent::getSlugOptions()->selfHealing(separator: '--');
    }
}
