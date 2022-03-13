<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableModelSoftDeletes;

beforeEach(function () {
    $this->translatableTestModel = new class () extends TranslatableModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };

    $this->secTranslatableTestModel = new class () extends TranslatableModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };

    $this->testModel = new class () extends TestModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };

    $this->translatableModelSoftDeletes = new class () extends TranslatableModelSoftDeletes {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->arabicable();
        }
    };
});

it('generates a slug for each translation', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-عربي-للاختبار-للغة-الانجليزية');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-عربي-للاختبار-للغة-الالمانية');
});

it('can update one of the translations', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي معدل للغة الانجليزية');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-عربي-معدل-للغة-الانجليزية');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-عربي-للاختبار-للغة-الالمانية');
});

it('can update all translations', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي معدل للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي معدل للغة الالمانية');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-عربي-معدل-للغة-الانجليزية');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-عربي-معدل-للغة-الالمانية');
});

it('can make the slug unique for each language', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $this->translatableTestModel->save();

    $secondTestModel = $this->secTranslatableTestModel->create([
        'name' => [
            'en' => 'نص عربي للاختبار للغة الانجليزية',
            'nl' => 'نص عربي للاختبار للغة الالمانية',
        ],
    ]);

    expect($secondTestModel->slug)->toBe('نص-عربي-للاختبار-للغة-الانجليزية-1');
    expect($secondTestModel->getTranslation('slug', 'nl'))->toBe('نص-عربي-للاختبار-للغة-الالمانية-1');
});

it('can generate a slug based on multiple fields', function () {
    $this->translatableTestModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'other_field'])
            ->saveSlugsTo('slug')
    );

    $this->translatableTestModel->setTranslation('name', 'en', 'اسم بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'اسم بالالماني');
    $this->translatableTestModel->setTranslation('other_field', 'en', 'اسم اخر بالانجليزي');
    $this->translatableTestModel->setTranslation('other_field', 'nl', 'اسم اخر بالالماني');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('اسم-بالانجليزي-اسم-اخر-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('اسم-بالالماني-اسم-اخر-بالالماني');
});

it('handles fields that are not translatable', function () {
    $this->translatableTestModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'non_translatable_field'])
            ->saveSlugsTo('slug')
    );

    $this->translatableTestModel->setTranslation('name', 'en', 'اسم بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'اسم بالالماني');
    $this->translatableTestModel->non_translatable_field = 'رائع';
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('اسم-بالانجليزي-رائع');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('اسم-بالالماني-رائع');
});

it('uses the fallback language if one of the fields is not translated', function () {
    $this->translatableTestModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'other_field'])
            ->saveSlugsTo('slug')
    );

    $this->translatableTestModel->setTranslation('name', 'en', 'اسم بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'اسم بالالماني');
    $this->translatableTestModel->setTranslation('other_field', 'en', 'اسم اخر بالانجليزي');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('اسم-بالانجليزي-اسم-اخر-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('اسم-بالالماني-اسم-اخر-بالانجليزي');
});

it('can use a callback to generate a slug per language', function () {
    $this->translatableTestModel->useSlugOptions(
        SlugOptions::createWithLocales(['en', 'nl'])
            ->generateSlugsFrom(function ($model, $locale) {
                return implode(' ', [
                    $model->getTranslation('name', $locale, false),
                    $model->getTranslation('other_field', $locale, false),
                ]);
            })
            ->saveSlugsTo('slug')
    );

    $this->translatableTestModel->setTranslation('name', 'en', 'اسم بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'اسم بالالماني');
    $this->translatableTestModel->setTranslation('other_field', 'en', 'اسم اخر بالانجليزي');
    $this->translatableTestModel->setTranslation('other_field', 'nl', 'اسم اخر بالالماني');

    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('اسم-بالانجليزي-اسم-اخر-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('اسم-بالالماني-اسم-اخر-بالالماني');
});

it('can use a callback to update the slug per language', function () {
    $this->translatableTestModel->useSlugOptions(
        SlugOptions::createWithLocales(['en', 'nl'])
            ->generateSlugsFrom(function ($model, $locale) {
                return implode(' ', [
                    $model->getTranslation('name', $locale, false),
                    $model->getTranslation('other_field', $locale, false),
                ]);
            })
            ->saveSlugsTo('slug')
    );

    $this->translatableTestModel->setTranslation('name', 'en', 'اسم بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'اسم بالالماني');
    $this->translatableTestModel->setTranslation('other_field', 'en', '1');
    $this->translatableTestModel->setTranslation('other_field', 'nl', '1');

    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('other_field', 'en', '2');
    $this->translatableTestModel->setTranslation('other_field', 'nl', '2');

    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('اسم-بالانجليزي-2');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('اسم-بالالماني-2');
});

it('can handle overwrites when creating a model', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $this->translatableTestModel->setTranslation('slug', 'en', 'نص عربي معدل للغة الانجليزية');
    $this->translatableTestModel->setTranslation('slug', 'nl', 'نص عربي معدل للغة الالمانية');

    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-عربي-معدل-للغة-الانجليزية');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-عربي-معدل-للغة-الالمانية');
});

it('can handle overwrites when updating a model', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص بالالماني');
    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('slug', 'en', 'نص-معدل-بالانجليزي');
    $this->translatableTestModel->setTranslation('slug', 'nl', 'نص-معدل-بالالماني');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-معدل-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-معدل-بالالماني');
});

it('can handle overwrites for one item when updating a model', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص بالالماني');
    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('slug', 'nl', 'نص-معدل-بالالماني');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('نص-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-معدل-بالالماني');
});

it('can handle overwrites for one item when updating a model with custom slugs', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص بالالماني');
    $this->translatableTestModel->setTranslation('slug', 'en', 'رابط بالانجليزي');
    $this->translatableTestModel->setTranslation('slug', 'nl', 'رابط بالالماني');
    $this->translatableTestModel->save();

    $this->translatableTestModel->setTranslation('slug', 'nl', 'نص-معدل-بالالماني');
    $this->translatableTestModel->save();

    expect($this->translatableTestModel->slug)->toBe('رابط-بالانجليزي');
    expect($this->translatableTestModel->getTranslation('slug', 'nl'))->toBe('نص-معدل-بالالماني');
});

it('can handle duplicates when overwriting a slug', function () {
    $this->translatableTestModel->setTranslation('name', 'en', 'نص بالانجليزي');
    $this->translatableTestModel->setTranslation('name', 'nl', 'نص بالالماني');
    $this->translatableTestModel->save();

    $newModel = new $this->translatableTestModel();
    $newModel->setTranslation('name', 'en', 'نص اخر بالانجليزي');
    $newModel->setTranslation('name', 'nl', 'نص اخر بالالماني');
    $newModel->save();

    $newModel->setTranslation('slug', 'en', 'نص-بالانجليزي');
    $newModel->setTranslation('slug', 'nl', 'نص-بالالماني');
    $newModel->save();

    expect($newModel->slug)->toBe('نص-بالانجليزي-1');
    expect($newModel->getTranslation('slug', 'nl'))->toBe('نص-بالالماني-1');
});

it('can update slug with non unique names', function () {
    $model1 = new $this->translatableTestModel();
    $model1->setTranslation('name', 'en', 'نص للاختبار');
    $model1->save();

    $model2 = new $this->secTranslatableTestModel();
    $model2->setTranslation('name', 'en', 'نص للاختبار');
    $model2->save();

    $model2->setTranslation('name', 'en', 'نص متغيير للاختبار');
    $model2->save();

    expect($model2->getTranslation('slug', 'en'))->toBe('نص-متغيير-للاختبار');
});

it('can update slug with non unique names multiple', function () {
    $testModels = [];
    foreach (range(0, 15) as $i) {
        $model = new $this->translatableTestModel();
        $model->setTranslation('name', 'en', 'نص اختبار');
        $model->setTranslation('name', 'nl', 'نص اختبار');
        $model->save();

        $testModels[] = $model;
    }

    foreach (range(0, 15) as $i) {
        $model = $testModels[$i];
        $model->setTranslation('name', 'en', 'نص معدل');
        $model->save();

        $expectedSlug = 'نص-معدل' . ($i === 0 ? '' : '-' . $i);

        expect($model->getTranslation('slug', 'en'))->toBe($expectedSlug);
    }
});

it('can resolve route binding', function () {
    $model = new $this->translatableTestModel();

    $model->setTranslation('name', 'en', 'نص عربي للاختبار للغة الانجليزية');
    $model->setTranslation('name', 'nl', 'نص عربي للاختبار للغة الالمانية');
    $model->setTranslation('slug', 'en', 'نص-عربي-معدل-للغة-الانجليزية');
    $model->setTranslation('slug', 'nl', 'نص-عربي-معدل-للغة-الالمانية');
    $model->save();

    // Test for en locale
    $result = (new $this->translatableTestModel())->resolveRouteBinding('نص-عربي-معدل-للغة-الانجليزية', 'slug');

    expect($result)->not->toBeNull();
    expect($result->id)->toEqual($model->id);

    // Test for nl locale
    $this->app->setLocale('nl');

    $result = (new $this->translatableTestModel())->resolveRouteBinding('نص-عربي-معدل-للغة-الالمانية', 'slug');

    expect($result)->not->toBeNull();
    expect($result->id)->toEqual($model->id);

    // Test for fr locale - should fail
    app()->setLocale('fr');
    $result = (new $this->translatableTestModel())->resolveRouteBinding('نص-عربي-معدل-للغة-الالمانية', 'slug');

    expect($result)->toBeNull();
});

it('can resolve route binding even when soft deletes are on', function () {
    foreach (range(1, 10) as $i) {
        $model = new $this->translatableModelSoftDeletes();
        $model->setTranslation('name', 'en', 'نص انجليزي');
        $model->setTranslation('slug', 'en', 'نص-محدث-انجليزي-' . $i);
        $model->save();
        $model->delete();

        $result = (new $this->translatableModelSoftDeletes())->resolveSoftDeletableRouteBinding(
            'نص-محدث-انجليزي-' . $i,
            'slug'
        );

        expect($result)->not->toBeNull();
        expect($result->id)->toEqual($model->id);
    }
});

it('can bind route model implicit', function () {
    $model = new $this->translatableTestModel();
    $model->setTranslation('name', 'en', 'نص انجليزي');
    $model->setTranslation('slug', 'en', 'نص-محدث-انجليزي');
    $model->save();

    Route::get(
        '/translatable-model/{test:slug}',
        function (TranslatableModel $test) use ($model) {
            expect($test)->not->toBeNull();
            expect($test->id)->toEqual($model->id);
        }
    )->middleware(SubstituteBindings::class);

    $response = $this->get("/translatable-model/نص-محدث-انجليزي");

    $response->assertStatus(200);
});

it('can bind child route model implicit', function () {
    $model = new $this->translatableTestModel();
    $model->setTranslation('name', 'en', 'نص انجليزي');
    $model->setTranslation('slug', 'en', 'نص-محدث-انجليزي');
    $model->test_model_id = 1;
    $model->save();

    $parent = new TestModel();
    $parent->name = 'parent';
    $parent->save();

    Route::get(
        '/test-model/{test_model:url}/translatable-model/{translatable_model:slug}',
        function (TestModel $testModel, TranslatableModel $translatableModel) use ($parent, $model) {
            $this->assertNotNull($parent);
            $this->assertNotNull($translatableModel);
            $this->assertEquals($parent->id, $testModel->id);
            $this->assertEquals($model->id, $translatableModel->id);
        }
    )->middleware(SubstituteBindings::class);

    $response = $this->get("/test-model/parent/translatable-model/نص-محدث-انجليزي");

    $response->assertStatus(200);
});
