<?php

namespace Spatie\Sluggable\Tests;

class HasScopeTest extends TestCase
{
    /** @test */
    public function it_generates_same_slug_for_each_scope()
    {
        $testModel = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);
        $testModel2 = ScopeableModel::create(['name' => 'name', 'scope_id' => 2]);

        $this->assertSame($testModel->slug, $testModel2->slug);
    }

    /** @test */
    public function it_generates_different_slug_for_same_scope()
    {
        $testModel = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);
        $testModel2 = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);

        $this->assertNotSame($testModel->slug, $testModel2->slug);
    }
}
