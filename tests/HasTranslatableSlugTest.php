<?php

namespace Spatie\Sluggable\Tests;

use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\SlugOptions;

class HasTranslatableSlugTest extends TestCase
{
    /** @var \Spatie\Translatable\Test\TranslatableModel */
    protected $testModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->testModel = new TranslatableModel();
    }

    /** @test */
    public function it_generates_a_slug_for_each_translation()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $this->assertSame('test-value-en', $this->testModel->slug);
        $this->assertSame('test-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_update_one_of_the_translations()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $this->testModel->setTranslation('name', 'en', 'Updated value EN');
        $this->testModel->save();

        $this->assertSame('updated-value-en', $this->testModel->slug);
        $this->assertSame('test-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_update_all_translations()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $this->testModel->setTranslation('name', 'en', 'Updated value EN');
        $this->testModel->setTranslation('name', 'nl', 'Updated value NL');
        $this->testModel->save();

        $this->assertSame('updated-value-en', $this->testModel->slug);
        $this->assertSame('updated-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_make_the_slug_unique_for_each_language()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $secondTestModel = TranslatableModel::create([
            'name' => [
                'en' => 'Test value EN',
                'nl' => 'Test value NL',
            ],
        ]);

        $this->assertSame('test-value-en-1', $secondTestModel->slug);
        $this->assertSame('test-value-nl-1', $secondTestModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_generate_a_slug_based_on_multiple_fields()
    {
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

        $this->assertSame('name-en-other-en', $this->testModel->slug);
        $this->assertSame('name-nl-other-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_handles_fields_that_are_not_translatable()
    {
        $this->testModel->useSlugOptions(
            SlugOptions::create()
                ->generateSlugsFrom(['name', 'non_translatable_field'])
                ->saveSlugsTo('slug')
        );

        $this->testModel->setTranslation('name', 'en', 'Name EN');
        $this->testModel->setTranslation('name', 'nl', 'Name NL');
        $this->testModel->non_translatable_field = 'awesome';
        $this->testModel->save();

        $this->assertSame('name-en-awesome', $this->testModel->slug);
        $this->assertSame('name-nl-awesome', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_uses_the_fallback_language_if_one_of_the_fields_is_not_translated()
    {
        $this->testModel->useSlugOptions(
            SlugOptions::create()
                ->generateSlugsFrom(['name', 'other_field'])
                ->saveSlugsTo('slug')
        );

        $this->testModel->setTranslation('name', 'en', 'Name EN');
        $this->testModel->setTranslation('name', 'nl', 'Name NL');
        $this->testModel->setTranslation('other_field', 'en', 'Other EN');
        $this->testModel->save();

        $this->assertSame('name-en-other-en', $this->testModel->slug);
        $this->assertSame('name-nl-other-en', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_use_a_callback_to_generate_a_slug_per_language()
    {
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

        $this->assertSame('name-en-other-en', $this->testModel->slug);
        $this->assertSame('name-nl-other-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_use_a_callback_to_update_the_slug_per_language()
    {
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

        $this->assertSame('name-en-2', $this->testModel->slug);
        $this->assertSame('name-nl-2', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_handle_overwrites_when_creating_a_model()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->setTranslation('slug', 'en', 'updated-value-en');
        $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');

        $this->testModel->save();

        $this->assertSame('updated-value-en', $this->testModel->slug);
        $this->assertSame('updated-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_handle_overwrites_when_updating_a_model()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $this->testModel->setTranslation('slug', 'en', 'updated-value-en');
        $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
        $this->testModel->save();

        $this->assertSame('updated-value-en', $this->testModel->slug);
        $this->assertSame('updated-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_handle_overwrites_for_one_item_when_updating_a_model()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->save();

        $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
        $this->testModel->save();

        $this->assertSame('test-value-en', $this->testModel->slug);
        $this->assertSame('updated-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_handle_overwrites_for_one_item_when_updating_a_model_with_custom_slugs()
    {
        $this->testModel->setTranslation('name', 'en', 'Test value EN');
        $this->testModel->setTranslation('name', 'nl', 'Test value NL');
        $this->testModel->setTranslation('slug', 'en', 'Test slug EN');
        $this->testModel->setTranslation('slug', 'nl', 'Test slug NL');
        $this->testModel->save();

        $this->testModel->setTranslation('slug', 'nl', 'updated-value-nl');
        $this->testModel->save();

        $this->assertSame('test-slug-en', $this->testModel->slug);
        $this->assertSame('updated-value-nl', $this->testModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_handle_duplicates_when_overwriting_a_slug()
    {
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

        $this->assertSame('test-value-en-1', $newModel->slug);
        $this->assertSame('test-value-nl-1', $newModel->getTranslation('slug', 'nl'));
    }

    /** @test */
    public function it_can_update_slug_with_non_unique_names()
    {
        $model1 = new TranslatableModel();
        $model1->setTranslation('name', 'en', 'Test Value');
        $model1->save();

        $model2 = new TranslatableModel();
        $model2->setTranslation('name', 'en', 'Test Value');
        $model2->save();

        $model2->setTranslation('name', 'en', 'Changed Value');
        $model2->save();

        $this->assertSame('changed-value', $model2->getTranslation('slug', 'en'));
    }

    /** @test */
    public function it_can_update_slug_with_non_unique_names_multiple()
    {
        $testModels = [];
        foreach (range(0, 15) as $i) {
            $model = new TranslatableModel();
            $model->setTranslation('name', 'en', 'Test Value');
            $model->setTranslation('name', 'nl', 'Test Value');
            $model->save();

            array_push($testModels, $model);
        }

        foreach (range(0, 15) as $i) {
            $model = $testModels[$i];
            $model->setTranslation('name', 'en', 'Changed Value');
            $model->save();

            $expectedSlug = 'changed-value' . ($i === 0 ? '' : '-' . $i);
            $this->assertSame($expectedSlug, $model->getTranslation('slug', 'en'));
        }
    }

    /** @test */
    public function it_can_resolve_route_binding()
    {
        $model = new TranslatableModel();

        $model->setTranslation('name', 'en', 'Test value EN');
        $model->setTranslation('name', 'nl', 'Test value NL');
        $model->setTranslation('slug', 'en', 'updated-value-en');
        $model->setTranslation('slug', 'nl', 'updated-value-nl');
        $model->save();

        // Test for en locale
        $result = (new TranslatableModel())->resolveRouteBinding('updated-value-en', 'slug');

        $this->assertNotNull($result);
        $this->assertEquals($model->id, $result->id);

        // Test for nl locale
        $this->app->setLocale('nl');

        $result = (new TranslatableModel())->resolveRouteBinding('updated-value-nl', 'slug');

        $this->assertNotNull($result);
        $this->assertEquals($model->id, $result->id);

        // Test for fr locale - should fail
        $this->app->setLocale('fr');
        $result = (new TranslatableModel())->resolveRouteBinding('updated-value-nl', 'slug');
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_resolve_route_binding_even_when_soft_deletes_are_on()
    {
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

            $this->assertNotNull($result);
            $this->assertEquals($model->id, $result->id);
        }
    }
    /** @test */
    public function it_can_bind_route_model_implicit()
    {
        $model = new TranslatableModel();
        $model->setTranslation('name', 'en', 'Test value EN');
        $model->setTranslation('slug', 'en', 'updated-value-en');
        $model->save();

        Route::get(
            '/translatable-model/{test:slug}',
            function (TranslatableModel $test) use ($model) {
                $this->assertNotNull($test);
                $this->assertEquals($model->id, $test->id);
            }
        )->middleware('bindings');

        $response = $this->get("/translatable-model/updated-value-en");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_bind_child_route_model_implicit()
    {
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
        )->middleware('bindings');

        $response = $this->get("/test-model/parent/translatable-model/updated-value-en");

        $response->assertStatus(200);
    }
}
