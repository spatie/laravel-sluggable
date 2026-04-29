<?php

namespace Spatie\Sluggable\Actions;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Spatie\Sluggable\Exceptions\InvalidOption;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Support\TraitDetector;

class GenerateSlugAction
{
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

    public function shouldSkipBasedOnSkipWhen(SlugOptions $options): bool
    {
        if ($options->skipGenerateWhen === null) {
            return false;
        }

        return ($options->skipGenerateWhen)() === true;
    }

    protected function shouldSkipGeneration(Model $model, SlugOptions $options): bool
    {
        if ($this->shouldSkipBasedOnSkipWhen($options)) {
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
        if ($this->hasCustomSlugBeenUsed($model, $options)) {
            $current = $model->{$options->slugField};

            if (filled($current)) {
                return $current;
            }
        }

        return $this->slugifySource($this->getSlugSourceString($model, $options), $options);
    }

    protected function hasCustomSlugBeenUsed(Model $model, SlugOptions $options): bool
    {
        $slugField = $options->slugField;

        return $model->getOriginal($slugField) !== $model->{$slugField};
    }

    public function slugifySource(string $source, SlugOptions $options): string
    {
        return Str::slug($source, $options->slugSeparator, $options->slugLanguage);
    }

    /**
     * Build (and truncate) the source string used as input to slugification.
     * The two closures let translatable callers swap how a field is read
     * and how a user-supplied source closure is invoked.
     *
     * @param  Closure(string $fieldName): string  $fieldReader
     * @param  Closure(Closure): string|null  $closureCaller
     */
    public function buildSourceString(
        SlugOptions $options,
        Closure $fieldReader,
        ?Closure $closureCaller = null,
    ): string {
        if ($options->generateSlugFrom instanceof Closure) {
            $closureCaller ??= fn (Closure $source) => $source();
            $sourceString = $closureCaller($options->generateSlugFrom);
        } else {
            $sourceString = implode(
                $options->slugSeparator,
                array_map($fieldReader, $options->generateSlugFrom),
            );
        }

        return mb_substr($sourceString, 0, $options->maximumLength);
    }

    protected function getSlugSourceString(Model $model, SlugOptions $options): string
    {
        return $this->buildSourceString(
            $options,
            fn (string $fieldName): string => (string) data_get($model, $fieldName, ''),
            fn (Closure $source): string => (string) $source($model),
        );
    }

    public function makeUnique(string $slug, Model $model, SlugOptions $options): string
    {
        if ($slug === '' || $options->suffixGenerator !== null || str_contains($options->slugField, '->')) {
            return $this->makeUniqueIterative($slug, $model, $options);
        }

        $existing = $this->fetchExistingSlugVariants($slug, $model, $options);
        $originalIsTaken = in_array($slug, $existing, true);

        if (! $options->useSuffixOnFirstOccurrence) {
            if (! $originalIsTaken) {
                return $slug;
            }
        }

        $prefix = $slug.$options->slugSeparator;
        $usedSuffixes = $this->extractNumericSuffixes($existing, $prefix);

        $candidate = $options->startSlugSuffixFrom;
        while (isset($usedSuffixes[$candidate])) {
            $candidate++;
        }

        return $prefix.$candidate;
    }

    /**
     * @param  array<int, mixed>  $existing
     * @return array<int, true>
     */
    protected function extractNumericSuffixes(array $existing, string $prefix): array
    {
        $prefixLength = strlen($prefix);
        $usedSuffixes = [];

        foreach ($existing as $existingSlug) {
            $existingSlug = (string) $existingSlug;

            if (! str_starts_with($existingSlug, $prefix)) {
                continue;
            }

            $tail = substr($existingSlug, $prefixLength);

            if ($tail === '' || ! ctype_digit($tail)) {
                continue;
            }

            $usedSuffixes[(int) $tail] = true;
        }

        return $usedSuffixes;
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
            ->where(function (Builder $query) use ($options, $slug, $likePattern): void {
                $query->where($options->slugField, $slug)
                    ->orWhere($options->slugField, 'like', $likePattern);
            });

        if ($options->extraScopeCallback !== null) {
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
        if ($options->suffixGenerator !== null) {
            return ($options->suffixGenerator)($originalSlug, $iteration);
        }

        return (string) ($options->startSlugSuffixFrom + $iteration);
    }

    protected function otherRecordExistsWithSlug(string $slug, Model $model, SlugOptions $options): bool
    {
        $query = $model->newQuery()
            ->where($options->slugField, $slug)
            ->withoutGlobalScopes();

        if ($options->extraScopeCallback !== null) {
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
        return TraitDetector::uses($model, SoftDeletes::class);
    }

    public function ensureValidOptions(SlugOptions $options): void
    {
        if (is_array($options->generateSlugFrom)) {
            if ($options->generateSlugFrom === []) {
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
}
