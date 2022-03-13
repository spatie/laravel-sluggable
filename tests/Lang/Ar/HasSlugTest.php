<?php

use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TestModelSoftDeletes;


beforeEach(function () {
    $this->testModel = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };

    $this->testSoftDeleteModel = new class () extends TestModelSoftDeletes {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };
});

it('will save a slug when saving a model', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    expect($model->url)->toEqual('نص-عربي-للاختبار');
});

it('can handle null values when creating slugs', function () {
    $model = $this->testModel->create(['name' => null]);

    expect($model->url)->toEqual('-1');
});

it('will not change the slug when the source field is not changed', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    $model->other_field = 'نص اخر';
    $model->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار');
});

it('will use the source field if the slug field is empty', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    $model->url = null;
    $model->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار');
});

it('will update the slug when the source field is changed', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    $model->name = 'نص اخر للاختبار';
    $model->save();

    expect($model->url)->toEqual('نص-اخر-للاختبار');
});

it('will save a unique slug by default', function () {
    $this->testModel->create(['name' => 'نص عربي للاختبار']);

    foreach (range(1, 10) as $i) {
        $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

        expect($model->url)->toEqual("نص-عربي-للاختبار-{$i}");
    }
});

it('can generate slugs from multiple source fields', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->generateSlugsFrom(['name', 'other_field'])->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->other_field = 'نص عربي من حقل اخر';
    $model->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار-نص-عربي-من-حقل-اخر');
});

it('can generate slugs from a callable', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->generateSlugsFrom(function (TestModel $model): string {
                return 'مقال-' . $model->arabicSlug($model->name);
            })->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->save();

    expect($model->url)->toEqual('مقال-نص-عربي-للاختبار');
});

it('can generate duplicate slugs', function () {
    foreach (range(1, 10) as $ignored) {
        $model = new class () extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->allowDuplicateSlugs()->arabicable();
            }
        };

        $model->name = 'نص عربي للاختبار';
        $model->save();

        expect($model->url)->toEqual('نص-عربي-للاختبار');
    }
});

it('can generate slugs with a maximum length', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->slugsShouldBeNoLongerThan(5)->arabicable();
        }
    };

    $model->name = '123456789';
    $model->save();

    expect($model->url)->toEqual('12345');
});

it('can handle weird characters when generating the slug', function (string $weirdCharacter, string $normalCharacter) {
    $model = $this->testModel->create(['name' => $weirdCharacter]);

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
    $model = $this->testModel->create(['name' => 'là']);
    $model->setSlugOptions($model->getSlugOptions()->slugsShouldBeNoLongerThan(2));
    $model->generateSlug();

    expect($model->url)->toEqual('la');
});

it('can handle overwrites when updating a model', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    $model->url = 'هذا-رابط-لمقال';
    $model->save();

    expect($model->url)->toEqual('هذا-رابط-لمقال');
});

it('can handle duplicates when overwriting a slug', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);
    $otherModel = $this->testModel->create(['name' => 'نص عربي اخر']);

    $model->url = 'نص-عربي-اخر';
    $model->save();

    expect($model->url)->toEqual('نص-عربي-اخر-1');
});


it('has an method that prevents a slug being generated on creation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnCreate()->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->save();

    expect($model->url)->toBeNull();
});

it('has an method that prevents a slug being generated on update', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnUpdate()->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->save();

    $model->name = 'نص عربي اخر للاختبار';
    $model->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار');
});

it('has an method that prevents a slug beign generated if already present', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->preventOverwrite()->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->url = 'بالفعل-تم-الانشاء';
    $model->save();

    expect($model->url)->toEqual('بالفعل-تم-الانشاء');
});

it('will use separator option for slug generation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingSeparator('_')->arabicable();
        }
    };

    $model->name = 'اختبار العلامة الفاصلة';
    $model->save();

    expect($model->url)->toEqual('اختبار_العلامة_الفاصلة');
});

it('will use language option for slug generation', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->usingLanguage('nl')->arabicable();
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
    $this->testSoftDeleteModel->create(['name' => 'نص عربي للاختبار', 'deleted_at' => date('Y-m-d h:i:s')]);

    foreach (range(1, 10) as $i) {
        $model = $this->testSoftDeleteModel->create(['name' => 'نص عربي للاختبار']);

        expect($model->url)->toEqual("نص-عربي-للاختبار-{$i}");
    }
});

it('will save a unique slug by default when replicating a model', function () {
    $model = $this->testModel->create(['name' => 'نص عربي للاختبار']);

    $replica = $model->replicate();
    $replica->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار');
    expect($replica->url)->toEqual('نص-عربي-للاختبار-1');
});

it('will save a unique slug when replicating a model that does not generates slugs on update', function () {
    $model = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->doNotGenerateSlugsOnUpdate()->arabicable();
        }
    };

    $model->name = 'نص عربي للاختبار';
    $model->save();

    $replica = $model->replicate();
    $replica->save();

    expect($model->url)->toEqual('نص-عربي-للاختبار');
    expect($replica->url)->toEqual('نص-عربي-للاختبار-1');
});
