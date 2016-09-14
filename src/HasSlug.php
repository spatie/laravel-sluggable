<?php

namespace Spatie\Sluggable;

use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    /**
     * Get the options for generating the slug.
     */
    abstract public function getSlugOptions(): SlugOptions;

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->addSlug();
        });

        static::updating(function (Model $model) {
            $model->addSlug();
        });
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        Slugger::setSlug($this->getSlugOptions(), $this);
    }
}
