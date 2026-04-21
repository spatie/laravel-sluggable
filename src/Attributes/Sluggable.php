<?php

namespace Spatie\Sluggable\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Sluggable
{
    /**
     * @param  string|array<int, string>  $from
     */
    public function __construct(
        public string|array $from = 'name',
        public string $to = 'slug',
        public string $separator = '-',
        public string $language = 'en',
        public int $maxLength = 250,
        public bool $unique = true,
        public bool $onCreate = true,
        public bool $onUpdate = true,
        public bool $preventOverwrite = false,
        public bool $selfHealing = false,
        public string $selfHealingSeparator = '-',
    ) {}
}
