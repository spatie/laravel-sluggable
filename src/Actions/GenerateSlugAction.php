<?php

namespace Spatie\Sluggable\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Spatie\Sluggable\Exceptions\InvalidOption;
use Spatie\Sluggable\SlugOptions;

class GenerateSlugAction
{
    public function onCreate(Model $model, SlugOptions $options): void
    {
        if ($options->skipGenerate) {
            return;
        }

        if (! $options->generateSlugsOnCreate) {
            return;
        }

        if ($options->preventOverwrite) {
            if ($model->{$options->slugField} !== null) {
                return;
            }
        }

        $this->addSlug($model, $options);
    }

    public function onUpdate(Model $model, SlugOptions $options): void
    {
        if ($options->skipGenerate) {
            return;
        }

        if (! $options->generateSlugsOnUpdate) {
            return;
        }

        if ($options->preventOverwrite) {
            if ($model->{$options->slugField} !== null) {
                return;
            }
        }

        $this->addSlug($model, $options);
    }

    public function generate(Model $model, SlugOptions $options): void
    {
        $this->addSlug($model, $options);
    }

    public function addSlug(Model $model, SlugOptions $options): void
    {
        $this->ensureValidOptions($options);

        $slug = $this->generateNonUniqueSlug($model, $options);

        if ($options->generateUniqueSlugs) {
            $slug = $this->makeUnique($slug, $model, $options);
        }

        $model->{$options->slugField} = $slug;
    }

    public function generateNonUniqueSlug(Model $model, SlugOptions $options): string
    {
        $slugField = $options->slugField;

        if ($this->hasCustomSlugBeenUsed($model, $options) && ! empty($model->{$slugField})) {
            return $model->{$slugField};
        }

        return Str::slug(
            $this->getSlugSourceString($model, $options),
            $options->slugSeparator,
            $options->slugLanguage,
        );
    }

    public function hasCustomSlugBeenUsed(Model $model, SlugOptions $options): bool
    {
        $slugField = $options->slugField;

        return $model->getOriginal($slugField) !== $model->{$slugField};
    }

    public function getSlugSourceString(Model $model, SlugOptions $options): string
    {
        if (is_callable($options->generateSlugFrom)) {
            return $this->truncate(($options->generateSlugFrom)($model), $options);
        }

        $sourceString = collect($options->generateSlugFrom)
            ->map(fn (string $fieldName): string => data_get($model, $fieldName, ''))
            ->implode($options->slugSeparator);

        return $this->truncate($sourceString, $options);
    }

    public function makeUnique(string $slug, Model $model, SlugOptions $options): string
    {
        $originalSlug = $slug;
        $iteration = 0;

        while (
            $slug === '' ||
            $this->otherRecordExistsWithSlug($slug, $model, $options) ||
            ($options->useSuffixOnFirstOccurrence && $iteration === 0)
        ) {
            $suffix = $this->generateSuffix($originalSlug, $iteration++, $options);
            $slug = $originalSlug.$options->slugSeparator.$suffix;
        }

        return $slug;
    }

    public function generateSuffix(string $originalSlug, int $iteration, SlugOptions $options): string
    {
        if ($options->suffixGenerator) {
            return ($options->suffixGenerator)($originalSlug, $iteration);
        }

        return (string) ($options->startSlugSuffixFrom + $iteration);
    }

    public function otherRecordExistsWithSlug(string $slug, Model $model, SlugOptions $options): bool
    {
        $query = $model->newQuery()
            ->where($options->slugField, $slug)
            ->withoutGlobalScopes();

        if ($options->extraScopeCallback) {
            $query->where($options->extraScopeCallback);
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->exists();
    }

    public function ensureValidOptions(SlugOptions $options): void
    {
        if (is_array($options->generateSlugFrom) && ! count($options->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if ($options->slugField === '') {
            throw InvalidOption::missingSlugField();
        }

        if ($options->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }

    protected function truncate(string $value, SlugOptions $options): string
    {
        return mb_substr($value, 0, $options->maximumLength);
    }
}
