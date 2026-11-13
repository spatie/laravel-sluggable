<?php

namespace Spatie\Sluggable\Tests\TestSupport;

use Spatie\Sluggable\SlugOptions;

class SelfHealingUuidModel extends UuidModel
{
    protected $table = 'uuid_models';

    public function getSlugOptions(): SlugOptions
    {
        return parent::getSlugOptions()->selfHealing(separator: '--');
    }
}
