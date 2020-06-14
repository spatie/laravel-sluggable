<?php

namespace Spatie\Sluggable\Tests;

use Spatie\Sluggable\SlugOptions;

class HasTranslatableSlugTest extends TestCase
{
    /** @var \Spatie\Translatable\Test\TranslatableModel */
    protected $testModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->testModel = new TranslatableModel;
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
}
