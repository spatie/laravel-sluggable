<?php

namespace Spatie\Sluggable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TestModel extends Model {
	use HasSlug;

	protected $table = 'test_models';

	protected $guarded = [];

	public $timestamps = false;

	/**
	 * Get the options for generating the slug.
	 */
	public function getSlugOptions() {
		return $this->slugOptions ?? $this->getDefaultSlugOptions();
	}

	/**
	 * Set the options for generating the slug.
	 */
	public function setSlugOptions(SlugOptions $slugOptions) {
		$this->slugOptions = $slugOptions;

		return $this;
	}

	/**
	 * Get the default slug options used in the tests.
	 */
	public function getDefaultSlugOptions() {
		return SlugOptions::create()
			->generateSlugsFrom('name')
			->saveSlugsTo('url');
	}
}
