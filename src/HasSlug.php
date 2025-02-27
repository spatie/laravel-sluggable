<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Sluggable\Exceptions\InvalidOption;

trait HasSlug
{
    protected SlugOptions $slugOptions;

    abstract public function getSlugOptions(): SlugOptions;

    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $model) {
            $model->generateSlugOnCreate();
        });

        static::updating(function (Model $model) {
            $model->generateSlugOnUpdate();
        });
    }

    protected function generateSlugOnCreate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if ($this->slugOptions->skipGenerate) {
            return;
        }

        if (! $this->slugOptions->generateSlugsOnCreate) {
            return;
        }

        if ($this->slugOptions->preventOverwrite) {
            if ($this->{$this->slugOptions->slugField} !== null) {
                return;
            }
        }

        $this->addSlug();
    }

    protected function generateSlugOnUpdate(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        if ($this->slugOptions->skipGenerate) {
            return;
        }

        if (! $this->slugOptions->generateSlugsOnUpdate) {
            return;
        }

        if ($this->slugOptions->preventOverwrite) {
            if ($this->{$this->slugOptions->slugField} !== null) {
                return;
            }
        }

        $this->addSlug();
    }

    public function generateSlug(): void
    {
        $this->slugOptions = $this->getSlugOptions();

        $this->addSlug();
    }

    protected function addSlug(): void
    {
        $this->ensureValidSlugOptions();

        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlugs) {
            $slug = $this->makeSlugUnique($slug);
        }

        $slugField = $this->slugOptions->slugField;

        $this->$slugField = $slug;
    }

    protected function generateNonUniqueSlug(): string
    {
        $slugField = $this->slugOptions->slugField;

        if ($this->hasCustomSlugBeenUsed() && ! empty($this->$slugField)) {
            return $this->$slugField;
        }

        return Str::slug($this->getSlugSourceString(), $this->slugOptions->slugSeparator, $this->slugOptions->slugLanguage);
    }

    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        return $this->getOriginal($slugField) != $this->$slugField;
    }

    protected function getSlugSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = $this->getSlugSourceStringFromCallable();

            return $this->generateSubstring($slugSourceString);
        }

        $slugSourceString = collect($this->slugOptions->generateSlugFrom)
            ->map(fn (string $fieldName): string => data_get($this, $fieldName, ''))
            ->implode($this->slugOptions->slugSeparator);

        return $this->generateSubstring($slugSourceString);
    }

    protected function getSlugSourceStringFromCallable(): string
    {
        return call_user_func($this->slugOptions->generateSlugFrom, $this);
    }

    protected function makeSlugUnique(string $slug): string
    {
        $originalSlug = $slug;
        $iteration = 0;

        while (
            $slug === '' ||
            $this->otherRecordExistsWithSlug($slug) ||
            ($this->slugOptions->useSuffixOnFirstOccurrence && $iteration === 0)
        ) {
            $suffix = $this->generateSuffix($originalSlug, $iteration++);
            $slug = $originalSlug . $this->slugOptions->slugSeparator . $suffix;
        }

        return $slug;
    }

    protected function generateSuffix(string $originalSlug, int $iteration): string
    {
        if ($this->slugOptions->suffixGenerator) {
            return call_user_func($this->slugOptions->suffixGenerator, $originalSlug, $iteration);
        }

        return strval($this->slugOptions->startSlugSuffixFrom + $iteration);
    }

    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        $query = static::where($this->slugOptions->slugField, $slug)
            ->withoutGlobalScopes();

        if ($this->slugOptions->extraScopeCallback) {
            $query->where($this->slugOptions->extraScopeCallback);
        }

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this), true);
    }

    protected function ensureValidSlugOptions(): void
    {
        if (is_array($this->slugOptions->generateSlugFrom) && ! count($this->slugOptions->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if (! strlen($this->slugOptions->slugField)) {
            throw InvalidOption::missingSlugField();
        }

        if ($this->slugOptions->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }

    /**
     * Helper function to handle multi-bytes strings if the module mb_substr is present,
     * default to substr otherwise.
     */
    protected function generateSubstring($slugSourceString)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($slugSourceString, 0, $this->slugOptions->maximumLength);
        }

        return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    public static function findBySlug(string $slug, array $columns = ['*'], ?callable $additionalQuery = null)
    {
        $modelInstance = new static();
        $field = $modelInstance->getSlugOptions()->slugField;

        $query = static::query();

        if (in_array(HasTranslatableSlug::class, class_uses_recursive(static::class))) {
            $currentLocale = $modelInstance->getLocale();
            $fallbackLocale = config('app.fallback_locale');

            $currentField = "{$field}->{$currentLocale}";
            $fallbackField = "{$field}->{$fallbackLocale}";

            $query->where(fn ($query) => $query->where($currentField, $slug)->orWhere($fallbackField, $slug));
        } else {
            $query->where($field, $slug);
        }

        if (is_callable($additionalQuery)) {
            $additionalQuery($query);
        }

        return $query->first($columns);
    }
}
