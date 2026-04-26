<?php

namespace Spatie\Sluggable;

use Closure;

class SlugOptions
{
    public const DEFAULT_SEPARATOR = '-';

    public const DEFAULT_LANGUAGE = 'en';

    public const DEFAULT_MAX_LENGTH = 250;

    /** @var array<int, string>|Closure */
    public array|Closure $generateSlugFrom;

    public ?Closure $skipGenerateWhen = null;

    public ?Closure $extraScopeCallback = null;

    /** @var (Closure(string, int): string)|null */
    public ?Closure $suffixGenerator = null;

    public string $slugField;

    public bool $generateUniqueSlugs = true;

    public int $maximumLength = self::DEFAULT_MAX_LENGTH;

    public bool $generateSlugsOnCreate = true;

    public bool $generateSlugsOnUpdate = true;

    public bool $preventOverwrite = false;

    public string $slugSeparator = self::DEFAULT_SEPARATOR;

    public string $slugLanguage = self::DEFAULT_LANGUAGE;

    /** @var array<int, string> */
    public array $translatableLocales = [];

    public int $startSlugSuffixFrom = 1;

    public bool $useSuffixOnFirstOccurrence = false;

    public bool $selfHealingUrls = false;

    public string $selfHealingSeparator = self::DEFAULT_SEPARATOR;

    public static function create(): static
    {
        return new static;
    }

    /**
     * @param  array<int, string>  $locales
     */
    public static function createWithLocales(array $locales): static
    {
        $slugOptions = static::create();
        $slugOptions->translatableLocales = $locales;

        return $slugOptions;
    }

    /**
     * @param  string|array<int, string>|Closure  $fieldName
     */
    public function generateSlugsFrom(string|array|Closure $fieldName): self
    {
        if (is_string($fieldName)) {
            $fieldName = [$fieldName];
        }

        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName): self
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs(): self
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength): self
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function skipGenerateWhen(Closure $callable): self
    {
        $this->skipGenerateWhen = $callable;

        return $this;
    }

    public function doNotGenerateSlugsOnCreate(): self
    {
        $this->generateSlugsOnCreate = false;

        return $this;
    }

    public function doNotGenerateSlugsOnUpdate(): self
    {
        $this->generateSlugsOnUpdate = false;

        return $this;
    }

    public function preventOverwrite(): self
    {
        $this->preventOverwrite = true;

        return $this;
    }

    public function usingSeparator(string $separator): self
    {
        $this->slugSeparator = $separator;

        return $this;
    }

    public function usingLanguage(string $language): self
    {
        $this->slugLanguage = $language;

        return $this;
    }

    public function extraScope(Closure $callbackMethod): self
    {
        $this->extraScopeCallback = $callbackMethod;

        return $this;
    }

    public function startSlugSuffixFrom(int $startSlugSuffixFrom): self
    {
        $this->startSlugSuffixFrom = max(1, $startSlugSuffixFrom);

        return $this;
    }

    public function useSuffixOnFirstOccurrence(): self
    {
        $this->useSuffixOnFirstOccurrence = true;

        return $this;
    }

    public function selfHealing(string $separator = self::DEFAULT_SEPARATOR): self
    {
        $this->selfHealingUrls = true;
        $this->selfHealingSeparator = $separator;

        return $this;
    }

    /**
     * @param  Closure(string $slug, int $iteration): string  $generator
     */
    public function usingSuffixGenerator(Closure $generator): self
    {
        $this->suffixGenerator = $generator;

        return $this;
    }
}
