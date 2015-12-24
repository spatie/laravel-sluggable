<?php

namespace Spatie\Sluggable\Test\Integration;

use Spatie\Sluggable\SlugOptions;

class HasSlugTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /**
     * @test
     */
    public function it_will_not_change_the_slug_when_the_source_field_is_not_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->other_field = 'otherValue';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /**
     * @test
     */
    public function it_will_update_the_slug_when_the_source_field_is_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->name = 'this is another test';
        $model->save();

        $this->assertEquals('this-is-another-test', $model->url);
    }

    /**
     * @test
     */
    public function it_will_save_a_unique_slug_by_default()
    {
        TestModel::create(['name' => 'this is a test']);

        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->url);
        }
    }

    /**
     * @test
     */
    public function it_can_handle_empty_source_fields()
    {
        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => '']);
            $this->assertEquals("-{$i}", $model->url);
        }
    }

    /**
     * @test
     */
    public function it_can_generate_duplicate_slugs()
    {
        foreach (range(1, 10) as $i) {
            $model = new class extends TestModel
            {
                public function getSlugOptions() : SlugOptions
                {
                    return parent::getSlugOptions()->duplicateSlugsAreOk();
                }
            };

            $model->name = 'this is a test';
            $model->save();

            $this->assertEquals('this-is-a-test', $model->url);
        }
    }

    /**
     * @test
     */
    public function it_can_generate_slugs_with_a_maximum_length()
    {
        $model = new class extends TestModel
        {
            public function getSlugOptions() : SlugOptions
            {
                return parent::getSlugOptions()->slugShouldBeNoLongerThan(5);
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
    public function it_can_handle_weird_characters_when_generating_the_slug(string $weirdCharacter, string $normalCharacter)
    {
        $model = TestModel::create(['name' => $weirdCharacter]);

        $this->assertEquals($normalCharacter, $model->url);
    }

    public function weirdCharacterProvider()
    {
        return [
            ['é', 'e'],
            ['è', 'e'],
            ['à', 'a'],
            ['a€', 'a'],
            ['ß', 'ss'],
            ['a/ ', 'a'],
        ];
    }
}
