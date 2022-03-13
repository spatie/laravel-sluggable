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
            if ($this->slugOptions->arabicable) {
                return $this->arabicSlug($this->$slugField,$this->slugOptions->slugSeparator);
            }

           return $this->$slugField;
        }

        if ($this->slugOptions->arabicable) {
            return $this->arabicSlug($this->getSlugSourceString(),$this->slugOptions->slugSeparator);
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
        $i = 1;

        while ($this->otherRecordExistsWithSlug($slug) || $slug === '') {
            $slug = $originalSlug.$this->slugOptions->slugSeparator.$i++;
        }

        return $slug;
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

    protected function arabicSlug($string,$separator = '-') {
        if (is_null($string)) {
            return "";
        }
    
        $string = trim($string);
    
        $string = mb_strtolower($string, "UTF-8");;
    
        $string = preg_replace("/[^a-z0-9_\sءاأإآؤئبتثجحخدذرزسشصضطظعغفقكلمنهويةى]#u/", "", $string);
    
        $string = preg_replace("/[\s-]+/", " ", $string);
    
        $string = preg_replace("/[\s_]/", $separator, $string);

        $weird = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', '/'=>'','€'=>'eur',
            'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T',
        );

        $string = strtr($string, $weird);

        return $string;
    }
}