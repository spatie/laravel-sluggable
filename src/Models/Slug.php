<?php

namespace Spatie\Sluggable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 *
 * Slug
 *
 * @property string locale
 * @property string name
 * @method Illuminate\Database\Eloquent\Builder|Slug whereLocale($value)
 * @method Illuminate\Database\Eloquent\Builder|Slug whereName($value)
 */

class Slug extends Model
{
    protected $fillable = ['name', 'locale'];
    public $timestamps = false;

    public function parent(): MorphTo
    {
        return $this->morphTo();
    }
}
