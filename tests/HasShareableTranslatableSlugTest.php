<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\Models\Slug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableShareableModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableShareableModelSoftDeletes;

beforeEach(function () {
    $this->testModel = new TranslatableShareableModel();
});

it('generates a slug for each translation', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');

    $this->testModel->save();

    expect($this->testModel->getSlug())->toBe('test-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('test-value-nl');
});

it('can update one of the translations', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('name', 'en', 'Updated value EN');
    $this->testModel->save();

    expect($this->testModel->getSlug())->toBe('updated-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('test-value-nl');
});

it('can update all translations', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setTranslation('name', 'en', 'Updated value EN');
    $this->testModel->setTranslation('name', 'nl', 'Updated value NL');
    $this->testModel->save();

    expect($this->testModel->getSlug())->toBe('updated-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('updated-value-nl');
});

it('can make the slug unique for each language', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $secondTestModel = TranslatableShareableModel::create([
        'name' => [
            'en' => 'Test value EN',
            'nl' => 'Test value NL',
        ],
    ]);

    expect($secondTestModel->getSlug())->toBe('test-value-en-1')
        ->and($secondTestModel->getSlug('nl'))->toBe('test-value-nl-1');
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

    expect($this->testModel->getSlug())->toBe('name-en-other-en')
        ->and($this->testModel->getSlug('nl'))->toBe('name-nl-other-nl');
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

    expect($this->testModel->getSlug())->toBe('name-en-awesome')
        ->and($this->testModel->getSlug('nl'))->toBe('name-nl-awesome');
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

    expect($this->testModel->getSlug())->toBe('name-en-other-en')
        ->and($this->testModel->getSlug('nl'))->toBe('name-nl-other-en');
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

    expect($this->testModel->getSlug())->toBe('name-en-other-en')
        ->and($this->testModel->getSlug('nl'))->toBe('name-nl-other-nl');
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

    expect($this->testModel->getSlug())->toBe('name-en-2')
        ->and($this->testModel->getSlug('nl'))->toBe('name-nl-2');
});

it('can handle overwrites when creating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');

    $this->testModel->setSlug('updated-value-en', 'en');
    $this->testModel->setSlug('updated-value-nl', 'nl');

    $this->testModel->save();

    expect($this->testModel->getSlug())->toBe('updated-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('updated-value-nl');
});

it('can handle overwrites when updating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setSlug('updated-value-en', 'en');
    $this->testModel->setSlug('updated-value-nl', 'nl');

    expect($this->testModel->getSlug())->toBe('updated-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('updated-value-nl');
});

it('can handle overwrites for one item when updating a model', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $this->testModel->setSlug('updated-value-nl', 'nl');

    expect($this->testModel->getSlug())->toBe('test-value-en')
        ->and($this->testModel->getSlug('nl'))->toBe('updated-value-nl');
});

it('can handle overwrites for one item when updating a model with custom slugs', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->setSlug('Test slug NL', 'nl');
    $this->testModel->setSlug('Test slug EN', 'en');
    $this->testModel->save();

    $this->testModel->setSlug('updated-value-nl', 'nl');

    expect($this->testModel->getSlug())->toBe('test-slug-en')
        ->and($this->testModel->getSlug('nl'))->toBe('updated-value-nl');
});

it('can handle duplicates when overwriting a slug', function () {
    $this->testModel->setTranslation('name', 'en', 'Test value EN');
    $this->testModel->setTranslation('name', 'nl', 'Test value NL');
    $this->testModel->save();

    $newModel = new $this->testModel();
    $newModel->setTranslation('name', 'en', 'Test value 2 EN');
    $newModel->setTranslation('name', 'nl', 'Test value 2 NL');
    $newModel->save();

    $newModel->setSlug('test-value-en', 'en');
    $newModel->setSlug('test-value-nl', 'nl');

    expect($newModel->getSlug())->toBe('test-value-en-1')
        ->and($newModel->getSlug('nl'))->toBe('test-value-nl-1');
});

it('can update slug with non unique names', function () {
    $model1 = new TranslatableShareableModel();
    $model1->setSlug('Test Value', 'en');
    $model1->save();

    $model2 = new TranslatableShareableModel();
    $model2->setSlug('Test Value', 'en');
    $model2->save();

    $model2->setSlug('Changed Value', 'en');

    expect($model2->getSlug('en'))->toBe('changed-value');
});

it('can resolve route binding', function () {
    $model = new TranslatableShareableModel();

    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setTranslation('name', 'nl', 'Test value NL');
    $model->save();

    // Test for en locale
    $result = (new TranslatableShareableModel())->resolveRouteBinding('test-value-en', 'slug');

    expect($result)->not->toBeNull()
        ->and($result->id)->toEqual($model->id);

    // Test for nl locale
    app()->setLocale('nl');

    $result = (new TranslatableShareableModel())->resolveRouteBinding('test-value-nl', 'slug');

    expect($result)->not->toBeNull()
        ->and($result->id)->toEqual($model->id);

    // Test for fr locale - should fail
    app()->setLocale('fr');
    $result = (new TranslatableShareableModel())->resolveRouteBinding('updated-value-nl', 'slug');

    expect($result)->toBeNull();
});

it('can resolve route binding even when soft deletes are on', function () {
    foreach (range(1, 10) as $i) {
        $model = new TranslatableShareableModelSoftDeletes();
        $model->setTranslation('name', 'en', 'Test value EN');
        $model->setSlug('updated-value-en-' . $i, 'en');
        $model->save();
        $model->delete();

        $result = (new TranslatableShareableModelSoftDeletes())->resolveSoftDeletableRouteBinding(
            'updated-value-en-' . $i,
            'slug'
        );

        expect($result)->not->toBeNull()
            ->and($result->id)->toEqual($model->id);
    }
});

it('can bind route model implicit', function () {
    $model = new TranslatableShareableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setSlug('updated-value-en', 'en');
    $model->save();

    Route::get(
        '/translatable-model/{test:slug}',
        function (TranslatableShareableModel $test) use ($model) {
            expect($test)->not->toBeNull()
                ->and($test->id)->toEqual($model->id);
        }
    )->middleware(SubstituteBindings::class);

    $response = $this->get("/translatable-model/updated-value-en");

    $response->assertStatus(200);
});

it('can bind child route model implicit', function () {
    $parent = new TestModel();
    $parent->name = 'parent';
    $parent->save();

    $model = new TranslatableShareableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->test_model_id = 1;
    $model->save();

    Route::get(
        '/test-model/{test_model:url}/translatable-shareable-model/{translatable_shareable_model:slug}/',
        function (TestModel $testModel, TranslatableShareableModel $translatableShareableModel) use ($parent, $model) {
            expect($parent)->not->toBeNull()
                ->and($translatableShareableModel)->not->toBeNull()
                ->and($parent->id)->toEqual($testModel->id)
                ->and($model->id)->toEqual($translatableShareableModel->id);
        }
    )->middleware(SubstituteBindings::class);

    $response = $this->get("/test-model/parent/translatable-shareable-model/test-value-en");

    $response->assertStatus(200);
});

it('clears all slugs when model is deleted', function () {
    $model = new TranslatableShareableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->save();
    $model->delete();

    expect(Slug::all()->isEmpty())->toBeTrue();
});

it('deletes slug when translation is deleted', function () {
    $model = new TranslatableShareableModel();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->setTranslation('name', 'nl', 'Test value NL');
    $model->save();

    $model->forgetAllTranslations('nl');
    $model->save();

    expect(Slug::whereLocale('nl')->exists())->not->toBeTrue()
        ->and(Slug::whereLocale('en')->exists())->toBeTrue();
});

it('keeps slugs when model is trashed', function () {
    $model = new TranslatableShareableModelSoftDeletes();
    $model->setTranslation('name', 'en', 'Test value EN');
    $model->save();
    $model->delete();

    expect(Slug::all()->count())->toBe(1);
});
