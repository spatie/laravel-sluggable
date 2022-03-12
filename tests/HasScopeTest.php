<?php

use Spatie\Sluggable\Tests\TestSupport\ScopeableModel;

it('generates same slug for each scope', function () {
    $testModel = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);
    $testModel2 = ScopeableModel::create(['name' => 'name', 'scope_id' => 2]);

    expect($testModel->slug)->toBe($testModel2->slug);
});

it('generates different slug for same scope', function () {
    $testModel = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);
    $testModel2 = ScopeableModel::create(['name' => 'name', 'scope_id' => 1]);

    expect($testModel->slug)->not->toBe($testModel2->slug);
});
