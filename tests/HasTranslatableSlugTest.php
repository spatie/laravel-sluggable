<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableModelSoftDeletes;

beforeEach(function () {
    $this->testModel = new TranslatableModel();
});

it('generates a slug for each translation', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('test-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('test-value-nl');
});

it('can update one of the translations', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('name', 'en', 'Updated value EN');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('updated-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('test-value-nl');
});

it('can update all translations', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('name', 'en', 'Updated value EN');
    $this->testModel->setTranslation('name', 'nl', 'Updated value NL');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('updated-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('updated-value-nl');
});

it('can make the slug unique for each language', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $secondTestModel = TranslatableModel::create([
        'name' => [
            'en' => 'Test value EN',
            'nl' => 'Test value NL',
        ],
    ]);

    expect($secondTestModel->slug)->toBe('test-value-en-1');
    expect($secondTestModel->getTranslation('slug', 'nl'))->toBe('test-value-nl-1');
});

it('can generate a slug based on multiple fields', function () {
    $this->testModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'other_field'])
            ->saveSlugsTo('slug')
    );

    $this->testModel->setTranslation('name', 'en', 'Name EN');
    $this->testModel->setTranslation('name', 'nl', 'Name NL');
    $this->testModel->setTranslation('other_field', 'en', 'Other EN');
    $this->testModel->setTranslation('other_field', 'nl', 'Other NL');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('name-en-other-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('name-nl-other-nl');
});

it('handles fields that are not translatable', function () {
    $this->testModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'non_translatable_field'])
            ->saveSlugsTo('slug')
    );

    $this->testModel->setTranslation('name', 'en', 'Name EN');
    $this->testModel->setTranslation('name', 'nl', 'Name NL');
    $this->testModel->non_translatable_field = 'awesome';
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('name-en-awesome');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('name-nl-awesome');
});

it('uses the fallback language if one of the fields is not translated', function () {
    $this->testModel->useSlugOptions(
        SlugOptions::create()
            ->generateSlugsFrom(['name', 'other_field'])
            ->saveSlugsTo('slug')
    );

    $this->testModel->setTranslation('name', 'en', 'Name EN');
    $this->testModel->setTranslation('name', 'nl', 'Name NL');
    $this->testModel->setTranslation('other_field', 'en', 'Other EN');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('name-en-other-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('name-nl-other-en');
});

it('can use a callback to generate a slug per language', function () {
    $this->testModel->useSlugOptions(
        SlugOptions::createWithLocales(['en', 'nl'])
            ->generateSlugsFrom(function ($model, $locale) {
                return implode(' ', [
                    $model->getTranslation('name', $locale, false),
                    $model->getTranslation('other_field', $locale, false),
                ]);
            })
            ->saveSlugsTo('slug')
    );

    $this->testModel->setTranslation('name', 'en', 'Name EN');
    $this->testModel->setTranslation('name', 'nl', 'Name NL');
    $this->testModel->setTranslation('other_field', 'en', 'Other EN');
    $this->testModel->setTranslation('other_field', 'nl', 'Other NL');

    $this->testModel->save();

    expect($this->testModel->slug)->toBe('name-en-other-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('name-nl-other-nl');
});

it('can use a callback to update the slug per language', function () {
    $this->testModel->useSlugOptions(
        SlugOptions::createWithLocales(['en', 'nl'])
            ->generateSlugsFrom(function ($model, $locale) {
                return implode(' ', [
                    $model->getTranslation('name', $locale, false),
                    $model->getTranslation('other_field', $locale, false),
                ]);
            })
            ->saveSlugsTo('slug')
    );

    $this->testModel->setTranslation('name', 'en', 'Name EN');
    $this->testModel->setTranslation('name', 'nl', 'Name NL');
    $this->testModel->setTranslation('other_field', 'en', '1');
    $this->testModel->setTranslation('other_field', 'nl', '1');

    $this->testModel->save();

    $this->testModel->setTranslation('other_field', 'en', '2');
    $this->testModel->setTranslation('other_field', 'nl', '2');

    $this->testModel->save();

    expect($this->testModel->slug)->toBe('name-en-2');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('name-nl-2');
});

it('can handle overwrites when creating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->setTranslation('slug', 'en', 'updated-value-en');
    $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');

    $this->testModel->save();

    expect($this->testModel->slug)->toBe('updated-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('updated-value-nl');
});

it('can handle overwrites when updating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('slug', 'en', 'updated-value-en');
    $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('updated-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('updated-value-nl');
});

it('can handle overwrites for one item when updating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('test-value-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('updated-value-nl');
});

it('can handle overwrites for one item when updating a model with custom slugs', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->setTranslation('slug', 'en', 'Test slug EN');
    $this->testModel->setTranslation('slug', 'nl', 'Test slug NL');
    $this->testModel->save();

    $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
    $this->testModel->save();

    expect($this->testModel->slug)->toBe('test-slug-en');
    expect($this->testModel->getTranslation('slug', 'nl'))->toBe('updated-value-nl');
});

it('can handle duplicates when overwriting a slug', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $newModel = new $this->testModel();
    $newModel->setTranslation('name', 'en', 'Test value 2 EN');
    $newModel->setTranslation('name', 'nl', 'Test value 2 NL');
    $newModel->save();

    $newModel->setTranslation('slug', 'en', 'test-value-en');
    $newModel->setTranslation('slug', 'nl', 'test-value-nl');
    $newModel->save();

    expect($newModel->slug)->toBe('test-value-en-1');
    expect($newModel->getTranslation('slug', 'nl'))->toBe('test-value-nl-1');
});

it('can update slug with non unique names', function () {
    $model1 = new TranslatableModel();
    $model1->setTranslation('name', 'en', 'Test Value');
    $model1->save();

    $model2 = new TranslatableModel();
    $model2->setTranslation('name', 'en', 'Test Value');
    $model2->save();

    $model2->setTranslation('name', 'en', 'Changed Value');
    $model2->save();

    expect($model2->getTranslation('slug', 'en'))->toBe('changed-value');
});

it('can update slug with non unique names multiple', function () {
    $testModels = [];
    foreach (range(0, 15) as $i) {
        $model = new TranslatableModel();
        $model->setTranslation('name', 'en', 'Test Value');
        $model->setTranslation('name', 'nl', 'Test Value');
        $model->save();

        $testModels[] = $model;
    }

    foreach (range(0, 15) as $i) {
        $model = $testModels[$i];
        $model->setTranslation('name', 'en', 'Changed Value');
        $model->save();

        $expectedSlug = 'changed-value' . ($i === 0 ? '' : '-' . $i);

        expect($model->getTranslation('slug', 'en'))->toBe($expectedSlug);
    }
});

it('can resolve route binding', function () {
    $model = new TranslatableModel();

    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setTranslation('name', 'nl', 'Test value NL');
    $model->setTranslation('slug', 'en', 'updated-value-en');
    $model->setTranslation('slug', 'nl', 'updated-value-nl');
    $model->save();

    // Test for en locale
    $result = (new TranslatableModel())->resolveRouteBinding('updated-value-en', 'slug');

    expect($result)->not->toBeNull();
    expect($result->id)->toEqual($model->id);

    // Test for nl locale
    $this->app->setLocale('nl');

    $result = (new TranslatableModel())->resolveRouteBinding('updated-value-nl', 'slug');

    expect($result)->not->toBeNull();
    expect($result->id)->toEqual($model->id);

    // Test for fr locale - should fail
    app()->setLocale('fr');
    $result = (new TranslatableModel())->resolveRouteBinding('updated-value-nl', 'slug');

    expect($result)->toBeNull();
});

it('can resolve route binding even when soft deletes are on', function () {
    foreach (range(1, 10) as $i) {
        $model = new TranslatableModelSoftDeletes();
        $model->setTranslation('name', 'en', 'Test value EN');
        $model->setTranslation('slug', 'en', 'updated-value-en-' . $i);
        $model->save();
        $model->delete();

        $result = (new TranslatableModelSoftDeletes())->resolveSoftDeletableRouteBinding(
            'updated-value-en-' . $i,
            'slug'
        );

        expect($result)->not->toBeNull();
        expect($result->id)->toEqual($model->id);
    }
});

it('can bind route model implicit', function () {
    $model = new TranslatableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setTranslation('slug', 'en', 'updated-value-en');
    $model->save();

    Route::get(
        '/translatable-model/{test:slug}',
        function (TranslatableModel $test) use ($model) {
            expect($test)->not->toBeNull();
            expect($test->id)->toEqual($model->id);
        }
    )->middleware(SubstituteBindings::class);

    $response = $this->get("/translatable-model/updated-value-en");

    $response->assertStatus(200);
});

it('can bind child route model implicit', function () {
    $model = new TranslatableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setTranslation('slug', 'en', 'updated-value-en');
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

    $response = $this->get("/test-model/parent/translatable-model/updated-value-en");

    $response->assertStatus(200);
});

it('can find models using findBySlug alias', function () {
    $model = new class () extends TranslatableModel {
        public function getSlugOptions(): SlugOptions
        {
            return parent::getSlugOptions()->saveSlugsTo('slug');
        }
    };

    $model->name = 'my custom url';
    $model->save();

    $savedModel = $model::findBySlug('my-custom-url');

    expect($savedModel->id)->toEqual($model->id);
});
