<?php

namespace Spatie\Sluggable;

class SlugOptions {
	/** @var string|array|callable */
	public $generateSlugFrom;

	/** @var string */
	public $slugField;

	/** @var bool */
	public $generateUniqueSlugs = true;

	/** @var int */
	public $maximumLength = 250;

	public static function create() {
		return new static();
	}

	/**
	 * @param string|array|callable $fieldName
	 *
	 * @return \Spatie\Sluggable\SlugOptions
	 */
	public function generateSlugsFrom($fieldName) {
		$this->generateSlugFrom = $fieldName;

		return $this;
	}

	public function saveSlugsTo(string $fieldName) {
		$this->slugField = $fieldName;

		return $this;
	}

	public function allowDuplicateSlugs() {
		$this->generateUniqueSlugs = false;

		return $this;
	}

	public function slugsShouldBeNoLongerThan(int $maximumLength) {
		$this->maximumLength = $maximumLength;

		return $this;
	}
}
