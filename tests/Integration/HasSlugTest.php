<?php

namespace Spatie\Sluggable\Test\Integration;

class HasSlugTest extends TestCase {
	/** @test */
	public function itWillSaveASlugWhenSavingAModel() {
		$model = TestModel::create(['name' => 'this is a test']);

		$this->assertEquals('this-is-a-test', $model->url);
	}

	/** @test */
	public function itCanHandleNullValuesWhenCreatingSlugs() {
		$model = TestModel::create(['name' => null]);

		$this->assertEquals('-1', $model->url);
	}

	/** @test */
	public function itWillNotChangeTheSlugWhenTheSourceFieldIsNotChanged() {
		$model = TestModel::create(['name' => 'this is a test']);

		$model->other_field = 'otherValue';
		$model->save();

		$this->assertEquals('this-is-a-test', $model->url);
	}

	/** @test */
	public function itWillUpdateTheSlugWhenTheSourceFieldIsChanged() {
		$model = TestModel::create(['name' => 'this is a test']);

		$model->name = 'this is another test';
		$model->save();

		$this->assertEquals('this-is-another-test', $model->url);
	}

	/** @test */
	public function itWillSaveAUniqueSlugByDefault() {
		TestModel::create(['name' => 'this is a test']);

		foreach (range(1, 10) as $i) {
			$model = TestModel::create(['name' => 'this is a test']);
			$this->assertEquals("this-is-a-test-{$i}", $model->url);
		}
	}

	/** @test */
	public function itCanHandleEmptySourceFields() {
		foreach (range(1, 10) as $i) {
			$model = TestModel::create(['name' => '']);
			$this->assertEquals("-{$i}", $model->url);
		}
	}

	/** @test */
	public function itCanGenerateSlugsFromMultipleSourceFields() {
		$model = new class extends TestModel {
			public function getSlugOptions() {
				return parent::getSlugOptions()->generateSlugsFrom(['name', 'other_field']);
			}
		};

		$model->name = 'this is a test';
		$model->other_field = 'this is another field';
		$model->save();

		$this->assertEquals('this-is-a-test-this-is-another-field', $model->url);
	}

	/** @test */
	public function itCanGenerateSlugsFromACallable() {
		$model = new class extends TestModel {
			public function getSlugOptions() {
				return parent::getSlugOptions()->generateSlugsFrom(function (TestModel $model): string {
					return 'foo-' . str_slug($model->name);
				});
			}
		};

		$model->name = 'this is a test';
		$model->save();

		$this->assertEquals('foo-this-is-a-test', $model->url);
	}

	/** @test */
	public function itCanGenerateDuplicateSlugs() {
		foreach (range(1, 10) as $i) {
			$model = new class extends TestModel {
				public function getSlugOptions() {
					return parent::getSlugOptions()->allowDuplicateSlugs();
				}
			};

			$model->name = 'this is a test';
			$model->save();

			$this->assertEquals('this-is-a-test', $model->url);
		}
	}

	/** @test */
	public function itCanGenerateSlugsWithAMaximumLength() {
		$model = new class extends TestModel {
			public function getSlugOptions() {
				return parent::getSlugOptions()->slugsShouldBeNoLongerThan(5);
			}
		};

		$model->name = '123456789';
		$model->save();

		$this->assertEquals('12345', $model->url);
	}

	/**
	 * @test
	 * @dataProvider weirdCharacterProvider
	 */
	public function itCanHandleWeirdCharactersWhenGeneratingTheSlug(string $weirdCharacter, string $normalCharacter) {
		$model = TestModel::create(['name' => $weirdCharacter]);

		$this->assertEquals($normalCharacter, $model->url);
	}

	public function weirdCharacterProvider() {
		return [
			['é', 'e'],
			['è', 'e'],
			['à', 'a'],
			['a€', 'a'],
			['ß', 'ss'],
			['a/ ', 'a'],
		];
	}

	/** @test */
	public function itCanHandleOverwritesWhenUpdatingAModel() {
		$model = TestModel::create(['name' => 'this is a test']);

		$model->url = 'this-is-an-url';
		$model->save();

		$this->assertEquals('this-is-an-url', $model->url);
	}

	/** @test */
	public function itCanHandleDuplicatesWhenOverwritingASlug() {
		$model = TestModel::create(['name' => 'this is a test']);
		$other_model = TestModel::create(['name' => 'this is an other']);

		$model->url = 'this-is-an-other';
		$model->save();

		$this->assertEquals('this-is-an-other-1', $model->url);
	}
}
