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
    /** @var array<class-string, bool> */
    protected static array $usesSoftDeletesCache = [];

    public function onCreate(Model $model, SlugOptions $options): void
    {
        if (! $options->generateSlugsOnCreate) {
            return;
        }

        if ($this->shouldSkipGeneration($model, $options)) {
            return;
        }

        $this->addSlug($model, $options);
    }

    public function onUpdate(Model $model, SlugOptions $options): void
    {
        if (! $options->generateSlugsOnUpdate) {
            return;
        }

        if ($this->shouldSkipGeneration($model, $options)) {
            return;
        }

        $this->addSlug($model, $options);
    }

    protected function shouldSkipGeneration(Model $model, SlugOptions $options): bool
    {
        if ($options->skipGenerateWhen !== null && ($options->skipGenerateWhen)() === true) {
            return true;
        }

        if (! $options->preventOverwrite) {
            return false;
        }

        return $model->{$options->slugField} !== null;
    }

    public function generate(Model $model, SlugOptions $options): void
    {
        $this->addSlug($model, $options);
    }

    protected function addSlug(Model $model, SlugOptions $options): void
    {
        $this->ensureValidOptions($options);

        $slug = $this->generateNonUniqueSlug($model, $options);

        if ($options->generateUniqueSlugs) {
            $slug = $this->makeUnique($slug, $model, $options);
        }

        $model->{$options->slugField} = $slug;
    }

    protected function generateNonUniqueSlug(Model $model, SlugOptions $options): string
    {
        $slugField = $options->slugField;

        if ($this->hasCustomSlugBeenUsed($model, $options)) {
            if (! empty($model->{$slugField})) {
                return $model->{$slugField};
            }
        }

        return Str::slug(
            $this->getSlugSourceString($model, $options),
            $options->slugSeparator,
            $options->slugLanguage,
        );
    }

    protected function hasCustomSlugBeenUsed(Model $model, SlugOptions $options): bool
    {
        $slugField = $options->slugField;

        return $model->getOriginal($slugField) !== $model->{$slugField};
    }

    protected function getSlugSourceString(Model $model, SlugOptions $options): string
    {
        if (is_callable($options->generateSlugFrom)) {
            return $this->truncate(($options->generateSlugFrom)($model), $options);
        }

        $sourceString = implode(
            $options->slugSeparator,
            array_map(fn (string $fieldName): string => (string) data_get($model, $fieldName, ''), $options->generateSlugFrom),
        );

        return $this->truncate($sourceString, $options);
    }

    public function makeUnique(string $slug, Model $model, SlugOptions $options): string
    {
        if ($slug === '' || $options->suffixGenerator !== null || str_contains($options->slugField, '->')) {
            return $this->makeUniqueIterative($slug, $model, $options);
        }

        $existing = $this->fetchExistingSlugVariants($slug, $model, $options);
        $originalIsTaken = in_array($slug, $existing, true);

        if (! $options->useSuffixOnFirstOccurrence && ! $originalIsTaken) {
            return $slug;
        }

        $prefix = $slug.$options->slugSeparator;
        $prefixLength = strlen($prefix);
        $usedSuffixes = [];

        foreach ($existing as $existingSlug) {
            if (! str_starts_with((string) $existingSlug, $prefix)) {
                continue;
            }

            $tail = substr((string) $existingSlug, $prefixLength);

            if ($tail !== '' && ctype_digit($tail)) {
                $usedSuffixes[(int) $tail] = true;
            }
        }

        $candidate = $options->startSlugSuffixFrom;
        while (isset($usedSuffixes[$candidate])) {
            $candidate++;
        }

        return $prefix.$candidate;
    }

    protected function makeUniqueIterative(string $slug, Model $model, SlugOptions $options): string
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

    /**
     * @return array<int, mixed>
     */
    protected function fetchExistingSlugVariants(string $slug, Model $model, SlugOptions $options): array
    {
        $likePattern = addcslashes($slug, '%_\\').$options->slugSeparator.'%';

        $query = $model->newQuery()
            ->withoutGlobalScopes()
            ->where(function ($query) use ($options, $slug, $likePattern): void {
                $query->where($options->slugField, $slug)
                    ->orWhere($options->slugField, 'like', $likePattern);
            });

        if ($options->extraScopeCallback) {
            $query->where($options->extraScopeCallback);
        }

        if ($model->exists) {
            $query->where($model->getKeyName(), '!=', $model->getKey());
        }

        if ($this->modelUsesSoftDeletes($model)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->pluck($options->slugField)->all();
    }

    protected function generateSuffix(string $originalSlug, int $iteration, SlugOptions $options): string
    {
        if ($options->suffixGenerator) {
            return ($options->suffixGenerator)($originalSlug, $iteration);
        }

        return (string) ($options->startSlugSuffixFrom + $iteration);
    }

    protected function otherRecordExistsWithSlug(string $slug, Model $model, SlugOptions $options): bool
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

        if ($this->modelUsesSoftDeletes($model)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->exists();
    }

    protected function modelUsesSoftDeletes(Model $model): bool
    {
        return self::$usesSoftDeletesCache[$model::class]
            ??= in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    public function ensureValidOptions(SlugOptions $options): void
    {
        if (is_array($options->generateSlugFrom)) {
            if (count($options->generateSlugFrom) === 0) {
                throw InvalidOption::missingFromField();
            }
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
