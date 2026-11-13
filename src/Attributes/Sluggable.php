<?php

namespace Spatie\Sluggable\Attributes;

use Attribute;
use Spatie\Sluggable\SlugOptions;

#[Attribute(Attribute::TARGET_CLASS)]
class Sluggable
{
    /**
     * @param  string|array<int, string>  $from
     */
    public function __construct(
        public string|array $from = 'name',
        public string $to = 'slug',
        public string $separator = SlugOptions::DEFAULT_SEPARATOR,
        public string $language = SlugOptions::DEFAULT_LANGUAGE,
        public int $maxLength = SlugOptions::DEFAULT_MAX_LENGTH,
        public bool $unique = true,
        public bool $onCreate = true,
        public bool $onUpdate = true,
        public bool $preventOverwrite = false,
        public bool $selfHealing = false,
        public string $selfHealingSeparator = SlugOptions::DEFAULT_SEPARATOR,
    ) {}
}
