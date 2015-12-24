<?php

namespace Spatie\Sluggable\Test\Integration;

class HasSlugTest extends TestCase
{
    /**
     * @test
     */
    public function it_will_save_a_slug()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $this->assertEquals('this-is-a-test', $model->url);
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
}
