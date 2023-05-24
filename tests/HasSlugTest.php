<?php

use Illuminate\Support\Str;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TestModelSoftDeletes;

it('will save a slug when saving a model', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    expect($model->url)->toEqual('this-is-a-test');
});

it('can handle null values when creating slugs', function () {
    $model = TestModel::create(['name' => null]);

    expect($model->url)->toEqual('-1');
});

it('will not change the slug when the source field is not changed', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    $model->other_field = 'otherValue';
    $model->save();

    expect($model->url)->toEqual('this-is-a-test');
});

it('will use the source field if the slug field is empty', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    $model->url = null;
    $model->save();

    expect($model->url)->toEqual('this-is-a-test');
});

it('will update the slug when the source field is changed', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    $model->name = 'this is another test';
    $model->save();

    expect($model->url)->toEqual('this-is-another-test');
});

it('will save a unique slug by default', function () {
    TestModel::create(['name' => 'this is a test']);

    foreach (range(1, 10) as $i) {
        $model = TestModel::create(['name' => 'this is a test']);

        expect($model->url)->toEqual("this-is-a-test-{$i}");
    }
});

it('can generate slugs from multiple source fields', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->generateSlugsFrom(['name', 'other_field']);
        }
    };

    $model->name = 'this is a test';
    $model->other_field = 'this is another field';
    $model->save();

    expect($model->url)->toEqual('this-is-a-test-this-is-another-field');
});

it('can generate slugs from a callable', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->generateSlugsFrom(function (TestModel $model): string {
                return 'foo-' . Str::slug($model->name);
            });
        }
    };

    $model->name = 'this is a test';
    $model->save();

    expect($model->url)->toEqual('foo-this-is-a-test');
});

it('can generate duplicate slugs', function () {
    foreach (range(1, 10) as $ignored) {
        $model = new class () extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->allowDuplicateSlugs();
            }
        };

        $model->name = 'this is a test';
        $model->save();

        expect($model->url)->toEqual('this-is-a-test');
    }
});

it('can generate slugs with a maximum length', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->slugsShouldBeNoLongerThan(5);
        }
    };

    $model->name = '123456789';
    $model->save();

    expect($model->url)->toEqual('12345');
});

it('can handle weird characters when generating the slug', function (string $weirdCharacter, string $normalCharacter) {
    $model = TestModel::create(['name' => $weirdCharacter]);

    expect($model->url)->toEqual($normalCharacter);
})->with([
    ['é', 'e'],
    ['è', 'e'],
    ['à', 'a'],
    ['a€', 'aeur'],
    ['ß', 'ss'],
    ['a/ ', 'a'],
]);


it('can handle multibytes characters cutting when generating the slug', function () {
    $model = TestModel::create(['name' => 'là']);
    $model->setSlugOptions($model->getSlugOptions()->slugsShouldBeNoLongerThan(2));
    $model->generateSlug();

    expect($model->url)->toEqual('la');
});

it('can handle overwrites when updating a model', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    $model->url = 'this-is-an-url';
    $model->save();

    expect($model->url)->toEqual('this-is-an-url');
});

it('can handle duplicates when overwriting a slug', function () {
    $model = TestModel::create(['name' => 'this is a test']);
    $otherModel = TestModel::create(['name' => 'this is an other']);

    $model->url = 'this-is-an-other';
    $model->save();

    expect($model->url)->toEqual('this-is-an-other-1');
});

it('has a method that prevents a slug being generated on condition', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()
                ->skipGenerateWhen(fn () => $this->name === 'draft');
        }
    };

    $model->name = 'draft';
    $model->save();

    expect($model->url)->toBeNull();

    $model->other_field = 'Spatie';
    $model->save();

    expect($model->url)->toBeNull();

    $model->name = 'this is not a draft';
    $model->save();

    expect($model->url)->toEqual('this-is-not-a-draft');
});

it('has a method that prevents a slug being generated on creation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnCreate();
        }
    };

    $model->name = 'this is a test';
    $model->save();

    expect($model->url)->toBeNull();
});

it('has a method that prevents a slug being generated on update', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnUpdate();
        }
    };

    $model->name = 'this is a test';
    $model->save();

    $model->name = 'this is another test';
    $model->save();

    expect($model->url)->toEqual('this-is-a-test');
});

it('has an method that prevents a slug beign generated if already present', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->preventOverwrite();
        }
    };

    $model->name = 'this is a test';
    $model->url = 'already-generated-slug';
    $model->save();

    expect($model->url)->toEqual('already-generated-slug');
});

it('will use separator option for slug generation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingSeparator('_');
        }
    };

    $model->name = 'this is a separator test';
    $model->save();

    expect($model->url)->toEqual('this_is_a_separator_test');
});

it('will use language option for slug generation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingLanguage('nl');
        }
    };

    expect($model->getSlugOptions()->slugLanguage)->toEqual('nl');
});

it('can generate language specific slugs', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingLanguage('en');
        }
    };

    $model->name = 'Güte nacht';
    $model->save();

    expect($model->url)->toEqual('gute-nacht');

    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingLanguage('de');
        }
    };

    $model->name = 'Güte nacht';
    $model->save();

    expect($model->url)->toEqual('guete-nacht');
});

it('will save a unique slug by default even when soft deletes are on', function () {
    TestModelSoftDeletes::create(['name' => 'this is a test', 'deleted_at' => date('Y-m-d h:i:s')]);

    foreach (range(1, 10) as $i) {
        $model = TestModelSoftDeletes::create(['name' => 'this is a test']);

        expect($model->url)->toEqual("this-is-a-test-{$i}");
    }
});

it('will save a unique slug by default when replicating a model', function () {
    $model = TestModel::create(['name' => 'this is a test']);

    $replica = $model->replicate();
    $replica->save();

    expect($model->url)->toEqual('this-is-a-test');
    expect($replica->url)->toEqual('this-is-a-test-1');
});

it('will save a unique slug when replicating a model that does not generates slugs on update', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnUpdate();
        }
    };

    $model->name = 'this is a test';
    $model->save();

    $replica = $model->replicate();
    $replica->save();

    expect($model->url)->toEqual('this-is-a-test');
    expect($replica->url)->toEqual('this-is-a-test-1');
});

it('can generate slug suffix starting from given number', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->startSlugSuffixFrom(2);
        }
    };

    $model->name = 'this is a test';
    $model->save();

    $replica = $model->replicate();
    $replica->save();

    expect($model->url)->toEqual('this-is-a-test');
    expect($replica->url)->toEqual('this-is-a-test-2');
});

it('can find models using findBySlug alias', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->saveSlugsTo('url');
        }
    };

    $model->name = 'my custom url';
    $model->save();

    $savedModel = $model::findBySlug('my-custom-url');

    expect($savedModel->id)->toEqual($model->id);
});
